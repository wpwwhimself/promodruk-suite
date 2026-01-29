<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MidoceanHandler extends ApiHandler
{
    #region constants
    private const URL = "https://api.midocean.com/gateway/";
    private const SUPPLIER_NAME = "Midocean";
    public function getPrefix(): array { return ["MO", "IT", "KC", "CX", "S"]; }
    private const PRIMARY_KEY = "master_id";
    public const SKU_KEY = "sku";
    public function getPrefixedId(string $original_sku): string { return $original_sku; }

    private array $imported_ids;
    #endregion

    #region auth
    public function authenticate(): void
    {
        // no auth required here
    }
    #endregion

    #region main
    public function downloadAndStoreAllProductData(): void
    {
        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        [
            "ids" => $ids,
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "marking_labels" => $marking_labels,
            "markings" => $markings,
            "marking_prices" => $marking_prices,
            "marking_manipulations" => $marking_manipulations,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $ids->count();
        $this->imported_ids = [];

        foreach ($ids as [$sku, $external_id]) {
            if ($this->sync->current_module_data["current_external_id"] != null && $this->sync->current_module_data["current_external_id"] > $external_id) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("sku", "products", "prices"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            if ($this->canProcessModule("marking")) {
                $this->prepareAndSaveMarkingData(compact("sku", "products", "markings", "marking_manipulations", "marking_labels", "marking_prices"));
            }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($this->imported_ids);
                $this->imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($this->imported_ids);

        $this->reportSynchCount($counter, $total);
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        if ($this->limit_to_module) {
            $product = $stock = $marking = false;
            ${$this->limit_to_module} = true;
        }

        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        $prices = ($product) ? $this->getPriceInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        [$marking_labels, $markings, $marking_prices, $marking_manipulations] = ($marking) ? $this->getMarkingInfo() : [collect(),collect(),collect(),collect()];

        $ids = $products->map(fn ($p) => [
            $p["master_code"],
            $p[self::PRIMARY_KEY],
        ]);

        return compact(
            "ids",
            "products",
            "prices",
            "stocks",
            "marking_labels",
            "markings",
            "marking_prices",
            "marking_manipulations",
        );
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
    #endregion

    #region processing
    /**
     * @param array $data sku, products, prices
     */
    public function prepareAndSaveProductData(array $data): array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
            "prices" => $prices,
        ] = $data;

        $product = $products->firstWhere("master_code", $sku);
        $variants = collect($product["variants"])
            ->groupBy(fn ($var) => $var["color_code"]);

        $imported_ids = [];
        $i = 0;

        foreach ($variants as $color_code => $size_variants) {
            $variant = $size_variants->first();
            $prepared_sku = isset($variant["size_textile"])
                ? Str::beforeLast($variant[self::SKU_KEY], "-".$variant["size_textile"])
                : $variant[self::SKU_KEY];

            $ordered_imgs = collect($variant["digital_assets"] ?? null)
                // ?->sortBy(fn ($imgdata) => basename($imgdata["url"], ".jpg"))
            ;

            $this->sync->addLog("in progress", 3, "saving product variant ".$prepared_sku."(".($i++ + 1)."/".count($variants).")", $product[self::PRIMARY_KEY]);
            $price = as_number($prices->firstWhere("variant_id", $variant["variant_id"])["price"] ?? null);
            $ret[] = $this->saveProduct(
                $prepared_sku,
                $variant["variant_id"],
                $product["short_description"],
                $product["long_description"] ?? null,
                $product["master_code"],
                $price,
                $ordered_imgs->pluck("url_highress")->toArray(),
                $ordered_imgs->pluck("url")->toArray(),
                Str::substr($variant[self::SKU_KEY], 0, 2),
                $this->processTabs($product, $variant),
                implode(" > ", array_filter([$variant["category_level1"], $variant["category_level2"] ?? null])),
                $variant["color_group"],
                source: self::SUPPLIER_NAME,
                sizes: isset($variant["size_textile"])
                    ? $size_variants->map(fn ($v) => [
                        "size_name" => $v["size_textile"],
                        "size_code" => $v["size_textile"],
                        "full_sku" => $v[self::SKU_KEY],
                    ])->toArray()
                    : null,
                marked_as_new: $variant["plc_status_description"] == "NEW",
            );

            $imported_ids[] = $prepared_sku;
        }

        // tally imported IDs
        $this->imported_ids = array_merge($this->imported_ids, $imported_ids);

        return $ret;
    }

    /**
     * @param array $data sku, stocks
     */
    public function prepareAndSaveStockData(array $data): array
    {
        [
            "sku" => $sku,
            "stocks" => $stocks,
        ] = $data;

        $sku_stocks = $stocks->filter(fn ($s) => Str::startsWith($s[self::SKU_KEY], $sku));
        $done = [];
        foreach ($sku_stocks as $stock) {
            $done[] = $this->saveStock(
                $stock["sku"],
                $stock["qty"],
                $stock["first_arrival_qty"] ?? null,
                isset($stock["first_arrival_date"]) ? Carbon::parse($stock["first_arrival_date"]) : null
            );
        }

        return $done;
    }

    /**
     * @param array $data sku, products, variant, markings, marking_manipulations, marking_labels, marking_prices
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
            "markings" => $markings,
            "marking_manipulations" => $marking_manipulations,
            "marking_labels" => $marking_labels,
            "marking_prices" => $marking_prices,
        ] = $data;

        $product = $products->firstWhere("master_code", $sku);
        foreach ($product["variants"] as $variant) {
            $product_for_marking = $markings->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY]);
            if (!$product_for_marking) return null;

            $prepared_sku = isset($variant["size_textile"])
                ? Str::beforeLast($variant[self::SKU_KEY], "-".$variant["size_textile"])
                : $variant[self::SKU_KEY];

            Product::find($prepared_sku)->update([
                "manipulation_cost" => ($marking_manipulations[$product_for_marking["print_manipulation"]] ?? 0),
            ]);

            $positions = $product_for_marking["printing_positions"] ?? [];

            foreach ($positions as $position) {
                foreach ($position["printing_techniques"] as $technique) {
                    $print_area_mm2 = $position["max_print_size_width"] * $position["max_print_size_height"];
                    $marking_price = $marking_prices->firstWhere("id", $technique["id"]);

                    for ($color_count = 1; $color_count <= max(1, $technique["max_colours"]); $color_count++) {
                        $ret[] = $this->saveMarking(
                            $prepared_sku,
                            $position["position_id"],
                            $marking_labels[$technique["id"]]
                            . (
                                $technique["max_colours"] > 0
                                ? " ($color_count kolor" . ($color_count >= 5 ? "ów" : ($color_count == 1 ? "" : "y")) . ")"
                                : ""
                            ),
                            implode("x", array_filter([
                                "$position[max_print_size_width]",
                                "$position[max_print_size_height] $position[print_size_unit]",
                            ])),
                            [collect($position["images"])->firstWhere("variant_color", $variant["color_code"])["print_position_image_with_area"] ?? null],
                            null, // multiple color pricing done as separate products, due to the way prices work
                            collect(
                                collect($marking_price["var_costs"])
                                    ->sortBy("area_from")
                                    ->last(fn ($c) => $c["area_from"] <= $print_area_mm2 / 100)["scales"]
                            )
                                ->mapWithKeys(fn ($p) => [
                                    str_replace(".", "", $p["minimum_quantity"]) => [
                                        "price" => $this->calculateMarkingPrice($p, $marking_price["pricing_type"], $color_count),
                                    ]
                                ])
                                ->toArray(),
                            as_number($marking_price["setup"]) * $color_count
                        );
                    }
                }
            }

            $this->deleteCachedUnsyncedMarkings();
        }

        return $ret;
    }

    private function calculateMarkingPrice(array $price_data, string $pricing_type, int $color_count = 1) {
        switch ($pricing_type) {
            case "NumberOfColours":
                return as_number($price_data["price"]) + $color_count * as_number($price_data["next_price"]);
            default:
                return as_number($price_data["price"]) + ($color_count - 1) * as_number($price_data["next_price"]);
        }
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
    #endregion
}
