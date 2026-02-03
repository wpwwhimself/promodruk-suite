<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AsgardHandler extends ApiHandler
{
    #region constants
    private const URL = "https://developers.bluecollection.eu/";
    private const SUPPLIER_NAME = "Asgard";
    public function getPrefix(): string { return "AS"; }
    private const PRIMARY_KEY = "id";
    public const SKU_KEY = "index";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }
    #endregion

    #region auth
    public function authenticate(): void
    {
        if (empty(session("asgard_token")))
            $this->prepareToken();

        $res = $this->testRequest(self::URL);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = $this->testRequest(self::URL);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
        }
    }

    private function testRequest($url)
    {
        return Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get($url . "api/categories/1");
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
            ]);
        session("asgard_token", $res->json("access"));
    }
    #endregion

    #region main
    public function downloadAndStoreAllProductData(): void
    {
        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        //! 🔥 synchronizacja musi działać w wyjątkowym trybie: 🔥 !//
        /**
         * Z jakiegoś powodu API Asgardu się męczy, jeśli zbyt często się je odpytuje. To sprawia, że regularne pobieranie 17 stron produktów może być wręcz niemożliwe.
         * Na szczęście wszystkie moduły potrzebują danych produktu, więc można to obejść.
         * Wobec tego rytm pobierania jest odwrócony - `downloadData` pobiera po jednej stronie, potem przetwarzam dane, a potem biorę kolejną.
         */
        //! wyjątki oznaczone prefiksem Overwritten Download Rhythm (odr) !//

        $odr_page = 1;
        $odr_is_last_page = false;

        while(!$odr_is_last_page) {
            [
                "ids" => $ids,
                "products" => $products,
                "categories" => $categories,
                "subcategories" => $subcategories,
                "marking_labels" => $marking_labels,
                "marking_prices" => $marking_prices,
                "odr_count" => $odr_count,
                "odr_is_last_page" => $odr_is_last_page,
            ] = $this->odrDownloadData(
                $this->sync->product_import_enabled,
                $this->sync->stock_import_enabled,
                $this->sync->marking_import_enabled,
                $odr_page
            );

            $this->sync->addLog("pending (info)", 1, "Ready to sync");

            $total = $odr_count;
            $imported_ids = [];

            foreach ($ids as [$sku, $external_id]) {
                $imported_ids[] = $external_id;

                if ($this->sync->current_module_data["current_external_id"] != null && $this->sync->current_module_data["current_external_id"] > $external_id) {
                    $counter++;
                    continue;
                }

                $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

                if ($this->canProcessModule("product")) {
                    $this->prepareAndSaveProductData(compact("sku", "products", "categories", "subcategories"));
                }

                if ($this->canProcessModule("stock")) {
                    $this->prepareAndSaveStockData(compact("sku", "products"));
                }

                if ($this->canProcessModule("marking")) {
                    $this->prepareAndSaveMarkingData(compact("sku", "products", "marking_labels", "marking_prices"));
                }

                $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

                $started_at ??= now();
                if ($started_at < now()->subMinutes(1)) {
                    if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($imported_ids);
                    $imported_ids = [];
                    $started_at = now();
                }
            }

            if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($imported_ids);

            $this->reportSynchCount($counter + (100 * ($odr_page - 1)), $total);

            $odr_page++;
        }
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        // odr: overridden
        return [];
    }

    public function odrDownloadData(bool $product, bool $stock, bool $marking, int $odr_page): array
    {
        if ($this->limit_to_module) {
            $product = $stock = $marking = false;
            ${$this->limit_to_module} = true;
        }

        [$products, $odr_count, $odr_is_last_page] = $this->getProductData($odr_page);
        [$categories, $subcategories] = ($product) ? $this->getCategoryData() : [collect(), collect()];
        [$marking_labels, $marking_prices] = ($marking) ? $this->getMarkingData($odr_page) : [collect(), collect()];

        $ids = $products->map(fn ($p) => [
            $p[self::SKU_KEY],
            $p[self::PRIMARY_KEY],
        ]);

        return compact(
            "ids",
            "products",
            "categories",
            "subcategories",
            "marking_labels",
            "marking_prices",
            "odr_count",
            "odr_is_last_page",
        );
    }

    private function getProductData(int $odr_page): array
    {
        $this->sync->addLog("pending (info)", 2, "pulling products data. This may take a while...");
        $data = collect();
        // $is_last_page = false; // odr: overridden
        // $page = 1; // odr: overridden
        $page = $odr_page;

        $this->refreshToken();
        // while (!$is_last_page) {
            $this->sync->addLog("pending (step)", 3, "page " . $page);
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/products-index", [
                    // "page" => $page++, // odr: overridden
                    "page" => $page,
                ])
                ->throwUnlessStatus(200)
                ->collect();
            $data = $data->merge($res["results"]);
            $count = $res["count"];
            $is_last_page = $res["next"] == null;
        // }

        return [$data, $count, $is_last_page];
    }

    private function getMarkingData(int $odr_page): array
    {
        $this->sync->addLog("pending (info)", 2, "pulling markings data. This may take a while...");

        // marking labels
        $labels = collect();
        // $is_last_page = false; // odr: overridden
        // $page = 1; // odr: overridden
        $page = $odr_page;

        $this->refreshToken();
        // while (!$is_last_page) {
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/marking-name", [
                    // "page" => $page++, // odr: overridden
                    "page" => $page,
                ])
                ->throwUnlessStatus(200)
                ->collect();
            $labels = $labels->merge($res["results"]);
            // $is_last_page = $res["next"] == null;
        // }
        $labels = $labels->pluck("name_pl", "id");

        // marking quantity prices
        $prices = collect();
        // $is_last_page = false; // odr: overridden
        // $page = 1;

        $this->refreshToken();
        // while (!$is_last_page) {
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/marking-price", [
                    // "page" => $page++,
                    "page" => $page,
                ])
                ->throwUnlessStatus(200)
                ->collect();
            $prices = $prices->merge($res["results"]);
            // $is_last_page = $res["next"] == null;
        // }

        return [$labels, $prices];
    }

    private function getCategoryData(): array
    {
        $this->sync->addLog("pending (info)", 2, "pulling categories data");
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
    #endregion

    #region processing
    /**
     * @param array $data sku, products, categories, subcategories
     */
    public function prepareAndSaveProductData(array $data): Product
    {
        [
            "sku" => $sku,
            "products" => $products,
            "categories" => $categories,
            "subcategories" => $subcategories,
        ] = $data;

        $product = $products->firstWhere(self::SKU_KEY, $sku);
        $name = collect($product["names"])->firstWhere("language", "pl")["title"];

        return $this->saveProduct(
            $product[self::SKU_KEY],
            $product[self::PRIMARY_KEY],
            $name,
            collect($product["descriptions"])->firstWhere("language", "pl")["text"],
            Str::beforeLast($product[self::SKU_KEY], "-"),
            collect($product["prices"])->first()["pln"],
            collect($product["image"])->sortBy("url")->pluck("url")->toArray(),
            collect($product["image"])->sortBy("url")->pluck("url")->map(function ($url) {
                $code = Str::afterLast($url, "/");
                $path = "https://bluecollection.gifts/media/catalog/product/$code[0]/$code[1]/$code";

                // test if the file really is there
                $definitely_empty_img = Http::get("https://bluecollection.gifts/media/catalog/product/aaa")->body();
                if ($definitely_empty_img == Http::get($path)->body()) {
                    $path .= ".jpg";
                }
                if ($definitely_empty_img == Http::get($path)->body()) {
                    $path = null;
                }

                return $path;
            })->toArray(),
            $this->getPrefix(),
            $this->processTabs($product, $product["marking_data"]),
            implode(" > ", [$categories[$product["category"]], $subcategories[$product["subcategory"]]]),
            collect($product["additional"])->firstWhere("item", "color_product")["value"],
            source: self::SUPPLIER_NAME,
            additional_services: collect($product["marking_data"])
                ->pluck("additional_service")
                ->flatten(1)
                ->map(fn ($service) => [
                    "id" => $service["id"],
                    "label" => $service["service_label"],
                    "price_per_unit" => as_number($service["service_price_pln"]),
                ])
                ->unique()
                ->toArray(),
            marked_as_new: $product["new_product"] ?? false,
        );
    }

    /**
     * @param array $data sku, products
     */
    public function prepareAndSaveStockData(array $data): Stock
    {
        [
            "sku" => $sku,
            "products" => $products,
        ] = $data;

        $product = $products->firstWhere(self::SKU_KEY, $sku);
        [$fd_amount, $fd_date] = $this->processFutureDelivery($product["future_delivery"]);

        return $this->saveStock(
            $this->getPrefixedId($product[self::SKU_KEY]),
            $product["quantity"],
            $fd_amount,
            $fd_date
        );
    }

    /**
     * @param array $data sku, products, position, technique, marking_labels, marking_prices
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
            "marking_labels" => $marking_labels,
            "marking_prices" => $marking_prices,
        ] = $data;

        $product = $products->firstWhere(self::SKU_KEY, $sku);
        $positions = $product["marking_data"][0]["marking_place"] ?? [];

        foreach ($positions as $position) {
            foreach ($position["marking_option"] as $technique) {
                for ($color_count = 1; $color_count <= max(1, $technique["max_colors"]); $color_count++) {
                    $ret[] = $this->saveMarking(
                        $this->getPrefixedId($product[self::SKU_KEY]),
                        "$position[name_pl] ($position[code])",
                        $marking_labels[$technique["option_label"]] . " ($technique[option_code])"
                            . (
                                $technique["max_colors"] > 0
                                ? " ($color_count kolor" . ($color_count >= 5 ? "ów" : ($color_count == 1 ? "" : "y")) . ")"
                                : ""
                            ),
                        $technique["option_info"],
                        [$technique["marking_area_img"]],
                        null, // multiple color pricing done as separate products, due to the way prices work
                        collect($marking_prices->firstWhere("code", $technique["option_code"] . ($color_count > 1 ? "_$color_count" : ""))["main_marking_price"])
                            ->mapWithKeys(fn ($p) => [$p["from_qty"] => [
                                "price" => $p["price_pln"],
                            ]])
                            ->toArray(),
                        0,
                    );
                }
            }
        }

        $this->deleteCachedUnsyncedMarkings();

        return $ret;
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
    #endregion
}
