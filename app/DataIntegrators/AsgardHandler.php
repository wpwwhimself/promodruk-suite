<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsgardHandler extends ApiHandler
{
    private const URL = "https://developers.bluecollection.eu/";
    private const SUPPLIER_NAME = "Asgard";
    public function getPrefix(): string { return "AS"; }
    private const PRIMARY_KEY = "id";
    private const SKU_KEY = "index";

    public function authenticate(): void
    {
        function testRequest($url)
        {
            return Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get($url . "api/categories/1")
                ->throwUnlessStatus(200);
        }
        if (empty(session("asgard_token")))
            $this->prepareToken();

        $res = testRequest(self::URL);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = testRequest(self::URL);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
        }
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        $products = $this->getProductData();
        if ($sync->product_import_enabled)
            [$categories, $subcategories] = $this->getCategoryData();

        [$marking_labels, $marking_prices] = ($sync->marking_import_enabled) ? $this->getMarkingData() : collect();

        try
        {
            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids[] = $this->getPrefix() . $product[self::SKU_KEY];

                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product[self::PRIMARY_KEY]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $this->getPrefix() . $product[self::SKU_KEY],
                        collect($product["names"])->firstWhere("language", "pl")["title"],
                        collect($product["descriptions"])->firstWhere("language", "pl")["text"],
                        $this->getPrefix() . Str::beforeLast($product[self::SKU_KEY], "-"),
                        collect($product["prices"])->first()["pln"],
                        collect($product["image"])->sortBy("url")->pluck("url")->toArray(),
                        collect($product["image"])->sortBy("url")->pluck("url")->map(function ($url) {
                            $code = Str::afterLast($url, "/");
                            $path = "https://bluecollection.gifts/media/catalog/product/$code[0]/$code[1]/$code";

                            // test if the file really is there
                            $definitely_empty_img = file_get_contents("https://bluecollection.gifts/media/catalog/product/aaa");
                            if ($definitely_empty_img == file_get_contents($path)) {
                                $path .= ".jpg";
                            }
                            if ($definitely_empty_img == file_get_contents($path)) {
                                $path = null;
                            }

                            return $path;
                        })->toArray(),
                        $product[self::SKU_KEY],
                        $this->processTabs($product, $product["marking_data"]),
                        implode(" > ", [$categories[$product["category"]], $subcategories[$product["subcategory"]]]),
                        collect($product["additional"])->firstWhere("item", "color_product")["value"],
                        source: self::SUPPLIER_NAME,
                    );
                }

                if ($sync->stock_import_enabled) {
                    [$fd_amount, $fd_date] = $this->processFutureDelivery($product["future_delivery"]);
                    $this->saveStock(
                        $this->getPrefix() . $product[self::SKU_KEY],
                        $product["quantity"],
                        $fd_amount,
                        $fd_date
                    );
                }

                if ($sync->marking_import_enabled) {
                    $positions = $product["marking_data"][0]["marking_place"] ?? [];

                    foreach ($positions as $position) {
                        foreach ($position["marking_option"] as $technique) {
                            $this->saveMarking(
                                $this->getPrefix() . $product[self::SKU_KEY],
                                "$position[name_pl] ($position[code])",
                                $marking_labels[$technique["option_label"]] . " ($technique[option_code])",
                                $technique["option_info"],
                                [$technique["marking_area_img"]],
                                $technique["max_colors"] > 0
                                    ? collect()->range(1, $technique["max_colors"])
                                        ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                                            "mod" => "*$i",
                                        ]])
                                        ->toArray()
                                    : null,
                                collect($marking_prices->firstWhere("code", $technique["option_code"])["main_marking_price"])
                                    ->mapWithKeys(fn ($p) => [$p["from_qty"] => [
                                        "price" => $p["price_pln"],
                                    ]])
                                    ->toArray(),
                            );
                        }
                    }
                }

                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress (step)", (++$counter / $total) * 100);
            }

            if ($sync->product_import_enabled) {
                $this->deleteUnsyncedProducts($sync, $imported_ids);
            }
            $this->reportSynchCount(self::SUPPLIER_NAME, $counter, $total);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "complete");
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY], "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
    }

    private function prepareToken()
    {
        $res = Http::acceptJson()
            ->post(self::URL . "api/token/", [
                "username" => env("ASGARD_API_LOGIN"),
                "password" => env("ASGARD_API_HASH_PASSWORD"),
            ])
            ->throwUnlessStatus(200);
        session([
            "asgard_token" => $res->json("access"),
            "asgard_refresh_token" => $res->json("refresh"),
        ]);
    }

    private function refreshToken()
    {
        $res = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->post(self::URL . "api/token/refresh", [
                "refresh" => session("asgard_refresh_token"),
            ])
            ->throwUnlessStatus(200);
        session("asgard_token", $res->json("access"));
    }

    private function getProductData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling products data. This may take a while...");
        $data = collect();
        $is_last_page = false;
        $page = 1;

        $this->refreshToken();
        while (!$is_last_page) {
            Log::debug(self::SUPPLIER_NAME . "> --- page $page...");
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/products-index", [
                    "page" => $page++,
                ])
                ->throwUnlessStatus(200)
                ->collect();
            $data = $data->merge($res["results"]);
            $is_last_page = $res["next"] == null;
        }

        return $data;
    }
    private function getMarkingData(): array
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling markings data. This may take a while...");

        // marking labels
        $labels = collect();
        $is_last_page = false;
        $page = 1;

        $this->refreshToken();
        while (!$is_last_page) {
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/marking-name", [
                    "page" => $page++,
                ])
                ->throwUnlessStatus(200)
                ->collect();
            $labels = $labels->merge($res["results"]);
            $is_last_page = $res["next"] == null;
        }
        $labels = $labels->pluck("name_pl", "id");

        // marking quantity prices
        $prices = collect();
        $is_last_page = false;
        $page = 1;

        $this->refreshToken();
        while (!$is_last_page) {
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/marking-price", [
                    "page" => $page++,
                ])
                ->throwUnlessStatus(200)
                ->collect();
            $prices = $prices->merge($res["results"]);
            $is_last_page = $res["next"] == null;
        }

        return [$labels, $prices];
    }
    private function getCategoryData(): array
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling categories data");
        $this->refreshToken();
        $categories = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::URL . "api/categories")
            ->throwUnlessStatus(200)
            ->collect("results")
            ->mapWithKeys(fn ($el) => [$el["id"] => $el["pl"]]);
        $subcategories = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::URL . "api/subcategories")
            ->throwUnlessStatus(200)
            ->collect("results")
            ->mapWithKeys(fn ($el) => [$el["id"] => $el["pl"]]);
        return [$categories, $subcategories];
    }

    private function processFutureDelivery(array $future_delivery) {
        if (count($future_delivery) == 0)
            return [null, null];

        // wybierz najbliższą dostawę
        $future_delivery = collect($future_delivery)
            ->sortBy("date")
            ->first();

        return [$future_delivery["quantity"], Carbon::parse($future_delivery["date"])];
    }

    private function processTabs(array $product, ?array $markings) {
        $all_fields = collect($product["additional"]);

        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification_fields = [
            "guarantee" => "Gwarancja w miesiącach",
            "pantone_color" => "Kolor Pantone produktu",
            "dimensions" => "Wymiary produktu",
            "ean_code" => "EAN",
            "custom_code" => "Kod celny",
            "color_product" => "Kolor",
            "material_pl" => "Materiał",
            "pen_nib_thickness" => "Grubość linii pisania (mm)",
            "pen_refill_type" => "Typ wkładu",
            "country_origin" => "Kraj pochodzenia",
            "ink_colour" => "Kolor wkładu",
            "soft_touch" => "Powierzchnia SOFT TOUCH",
            "length_of_writing" => "Długość pisania (metry)",
        ];
        $specification = [];
        foreach ($specification_fields as $item => $label) {
            $specification[$label] = $all_fields->firstWhere("item", $item)["value"];
        }

        //! packaging
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $packaging_fields = [
            "unit_package" => "Opakowanie produktu",
            "unit_weight" => "Waga jednostkowa brutto (kg)",
            "package_size" => "Wymiary opakowania jednostkowego",
            "qty_package" => "Ilość sztuk w kartonie",
            "package_dimension" => "Wymiary kartonu (cm)",
            "package_weight" => "Waga kartonu (kg)",
        ];
        $packaging = [];
        foreach ($packaging_fields as $item => $label) {
            $packaging[$label] = $all_fields->firstWhere("item", $item)["value"];
        }

        //! markings
        $marking_cells = collect($markings[0]["marking_place"] ?? [])
            ->map(fn($places) => [
                "heading" => "$places[name_pl] ($places[code])",
                "type" => "tiles",
                "content" => collect($places["marking_option"])
                    ->mapWithKeys(fn($option) => [$option["option_code"] => $option["marking_area_img"]])
            ])
            ->push([
                "type" => "table",
                "heading" => "Legenda",
                "content" => [
                    "Grawer" => "G0, G1, G2, G3, G4, G5",
                    "Tampodruk" => "N0, N1, N2, N3, N4",
                    "Sitodruk" => "S0, S1, S2",
                    "DTF" => "C1, C2, C3",
                    "Tłoczenie" => "T1, T2",
                    "Nadruk UV" => "U0, U1, U2, U3, U4",
                    "UV 360" => "U3, U4",
                    "Termotransfer sitodrukowy" => "F1, F2",
                    "Sublimacja" => "B1, B2",
                    "Haft komputerowy" => "H",
                    "Doming" => "D1, D2, D3",
                ]
            ])
            ->toArray();

        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return array_filter([
            [
                "name" => "Specyfikacja",
                "cells" => [["type" => "table", "content" => array_filter($specification ?? [])]]
            ],
            [
                "name" => "Pakowanie",
                "cells" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            ],
            [
                "name" => "Znakowanie",
                "cells" => $marking_cells,
            ],
        ]);
    }
}
