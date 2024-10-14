<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidoceanHandler extends ApiHandler
{
    private const URL = "https://api.midocean.com/gateway/";
    private const SUPPLIER_NAME = "Midocean";
    public function getPrefix(): array { return ["MO", "IT", "KC", "CX"]; }
    private const PRIMARY_KEY = "master_id";
    private const SKU_KEY = "sku";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        if ($sync->product_import_enabled)
            $prices = $this->getPriceInfo();
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo();
        if ($sync->marking_import_enabled)
            [$marking_labels, $markings, $marking_prices, $marking_manipulations] = $this->getMarkingInfo();

        try
        {
            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids = array_merge(
                    $imported_ids,
                    collect($product["variants"] ?? [])
                        ->map(fn ($v) => $v[self::SKU_KEY])
                        ->toArray(),
                );

                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                foreach ($product["variants"] as $variant) {
                    Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $variant[self::SKU_KEY]]);
                    $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product[self::PRIMARY_KEY]);

                    if ($sync->product_import_enabled) {
                        $this->saveProduct(
                            $variant[self::SKU_KEY],
                            $product["short_description"],
                            $product["long_description"] ?? null,
                            $product["master_code"],
                            as_number($prices->firstWhere("variant_id", $variant["variant_id"])["price"] ?? null),
                            collect($variant["digital_assets"] ?? null)?->sortBy("url")->pluck("url_highress")->toArray(),
                            collect($variant["digital_assets"] ?? null)?->sortBy("url")->pluck("url")->toArray(),
                            $variant["sku"],
                            $this->processTabs($product, $variant),
                            implode(" > ", array_filter([$variant["category_level1"], $variant["category_level2"] ?? null])),
                            $variant["color_group"],
                            source: self::SUPPLIER_NAME,
                        );
                    }

                    if ($sync->stock_import_enabled) {
                        $stock = $stocks->firstWhere("sku", $variant["sku"]);
                        if ($stock) {
                            $this->saveStock(
                                $variant["sku"],
                                $stock["qty"],
                                $stock["first_arrival_qty"] ?? null,
                                isset($stock["first_arrival_date"]) ? Carbon::parse($stock["first_arrival_date"]) : null
                            );
                        } else {
                            $this->saveStock($variant["sku"], 0);
                        }
                    }

                    if ($sync->marking_import_enabled) {
                        $product_for_marking = $markings->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY]);
                        if (!$product_for_marking) continue;

                        Product::find($variant[self::SKU_KEY])->update([
                            "manipulation_cost" => ($marking_manipulations[$product_for_marking["print_manipulation"]] ?? 0),
                        ]);

                        $positions = $product_for_marking["printing_positions"] ?? [];

                        foreach ($positions as $position) {
                            foreach ($position["printing_techniques"] as $technique) {
                                $print_area_mm2 = $position["max_print_size_width"] * $position["max_print_size_height"];

                                for ($color_count = 1; $color_count <= $technique["max_colours"]; $color_count++) {
                                    $this->saveMarking(
                                        $variant[self::SKU_KEY],
                                        $position["position_id"],
                                        $marking_labels[$technique["id"]]
                                        . (
                                            $technique["max_colours"] > 0
                                            ? " ($color_count kolor" . ($color_count >= 5 ? "ów" : ($color_count == 1 ? "" : "y")) . ")"
                                            : ""
                                        ),
                                        implode(" × ", array_filter([
                                            "$position[max_print_size_width] $position[print_size_unit]",
                                            "$position[max_print_size_height] $position[print_size_unit]",
                                        ])),
                                        [collect($position["images"])->firstWhere("variant_color", $variant["color_code"])["print_position_image_with_area"]],
                                        null, // multiple color pricing done as separate products, due to the way prices work
                                        collect(
                                            collect(
                                                $marking_prices->firstWhere("id", $technique["id"])["var_costs"]
                                            )
                                                ->last(fn ($c) => $c["area_from"] <= $print_area_mm2)["scales"]
                                        )
                                            ->mapWithKeys(fn ($p) => [
                                                str_replace(".", "", $p["minimum_quantity"]) => [
                                                    "price" => as_number($p["price"])
                                                        + ($color_count - 1) * as_number($p["next_price"]),
                                                ]
                                            ])
                                            ->toArray(),
                                        as_number($marking_prices->firstWhere("id", $technique["id"])["setup"]) * $color_count
                                    );
                                }
                            }
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

    private function getStockInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "stock/2.0", [])
            ->throwUnlessStatus(200)
            ->collect("stock");
    }

    private function getProductInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "products/2.0", [
                "language" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect()
            ->filter(fn ($p) => Str::startsWith($p["master_code"], $this->getPrefix()));
    }

    private function getPriceInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "pricelist/2.0", [])
            ->throwUnlessStatus(200)
            ->collect("price");
    }

    private function getMarkingInfo(): array
    {
        ["printing_technique_descriptions" => $marking_labels, "products" => $markings] =
        Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "printdata/1.0", [])
            ->throwUnlessStatus(200)
            ->collect();

        $marking_labels = collect($marking_labels)->mapWithKeys(fn ($i) => [
            $i["id"] => collect($i["name"])
                ->mapWithKeys(fn ($n) => [array_key_first($n) => array_values($n)[0]])
                ->firstWhere(fn ($n, $lang) => $lang == "pl")
        ]);

        $markings = collect($markings);

        ["print_manipulations" => $manipulations, "print_techniques" => $marking_prices] =
        Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "printpricelist/2.0", [])
            ->throwUnlessStatus(200)
            ->collect();

        $manipulations = collect($manipulations)->mapWithKeys(fn ($i) => [
            $i["code"] => as_number($i["price"])
        ]);

        /**
         * $marking_prices[technique code][print area in mm][minimum quantity] = price
         */
        $marking_prices = collect($marking_prices);

        return [$marking_labels, $markings, $marking_prices, $manipulations];
    }

    private function processTabs(array $product, array $variant) {
        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification_fields = [
            "dimensions" => "Wymiary produktu",
            "width;width_unit" => "Szerokość",
            "length;length_unit" => "Długość",
            "volume;volume_unit" => "Objetość",
            "gross_weight;gross_weight_unit" => "Waga",
            "net_weight;net_weight_unit" => "Waga netto",
            "material" => "Materiał",
            "commodity_code" => "Kod towaru",
            "country_of_origin" => "Kraj pochodzenia",
            "gtin" => "EAN",
            "pms_color" => "Kolor PMS",
        ];
        $specification = [];
        foreach ($specification_fields as $item => $label) {
            $specification[$label] = implode(" ", array_map(
                fn ($key) => $product[$key] ?? "—",
                explode(";", $item)
            ));
        }

        //! packaging
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $packaging_fields = [
            "carton_height;carton_height_unit" => "Wysokość kartonu",
            "carton_width;carton_width_unit" => "Szerokość kartonu",
            "carton_length;carton_length_unit" => "Długość kartonu",
            "carton_volume;carton_volume_unit" => "Objętość kartonu",
            "carton_gross_weight;carton_gross_weight_unit" => "Waga brutto kartonu",
            "outer_carton_quantity" => "Ilość sztuk w kartonie",
        ];
        $packaging = [];
        foreach ($packaging_fields as $item => $label) {
            $packaging[$label] = implode(" ", array_map(
                fn ($key) => $product[$key] ?? "—",
                explode(";", $item)
            ));
        }

        //! documents
        $documents = ["Pozycje nadruku (pobierz PDF)" => "https://www.midocean.com/INTERSHOP/web/WFS/midocean-PL-Site/pl_PL/-/PLN/ViewWeb2Print-DownloadPDFPrintProof?SKU=" . $variant["variant_id"]];

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
                "cells" => [["type" => "table", "content" => array_filter($specification ?? [])]],
            ],
            [
                "name" => "Opakowanie",
                "cells" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            ],
            [
                "name" => "Znakowanie",
                "cells" => [["type" => "tiles", "content" => array_filter($documents ?? [])]],
            ],
        ]);
    }
}
