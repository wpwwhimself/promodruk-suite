<?php

namespace App\DataIntegrators;

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
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now(), "synch_status" => 0]);

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()
            ->sortBy(self::PRIMARY_KEY);
        $prices = $this->getPriceInfo();
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo();

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                foreach ($product["variants"] as $variant) {
                    Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $variant[self::SKU_KEY]]);
                    ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY], "synch_status" => 1]);

                    if ($sync->product_import_enabled)
                    $this->saveProduct(
                        $variant["sku"],
                        $product["short_description"],
                        $product["long_description"] ?? null,
                        $product["master_code"],
                        str_replace(",", ".", $prices->firstWhere("variant_id", $variant["variant_id"])["price"]),
                        collect($variant["digital_assets"])->sortBy("url")->pluck("url_highress")->toArray(),
                        collect($variant["digital_assets"])->sortBy("url")->pluck("url")->toArray(),
                        $variant["sku"],
                        $this->processTabs($product, $variant),
                        implode(" > ", [$variant["category_level1"], $variant["category_level2"]]),
                        $variant["color_group"]
                    );

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
                }

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null, "synch_status" => 3]);
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY], "exception" => $e]);
            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["synch_status" => 2]);
        }
    }

    private function getStockInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "stock/2.0", [])
            ->collect("stock");
    }

    private function getProductInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "products/2.0", [
                "language" => "pl",
            ])
            ->collect();
    }

    private function getPriceInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "pricelist/2.0", [])
            ->collect("price");
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
