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
    public function getPrefix(): string { return "MO"; }

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()
            ->filter(fn ($p) => Str::startsWith($p["master_code"], $this->getPrefix()))
            ->sortBy("master_id");
        $prices = $this->getPriceInfo();
        if ($sync->stock_import_enabled)
        $stocks = $this->getStockInfo()
            ->filter(fn ($s) => Str::startsWith($s["sku"], $this->getPrefix()));

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product["master_id"]) {
                    $counter++;
                    continue;
                }

                foreach ($product["variants"] as $variant) {
                    Log::debug("-- downloading product " . $variant["sku"]);
                    ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["master_id"]]);

                    if ($sync->product_import_enabled)
                    $this->saveProduct(
                        $variant["sku"],
                        $product["short_description"],
                        $product["long_description"],
                        $product["master_code"],
                        str_replace(",", ".", $prices->firstWhere("variant_id", $variant["variant_id"])["price"]),
                        collect($variant["digital_assets"])->sortBy("url")->pluck("url_highress")->toArray(),
                        collect($variant["digital_assets"])->sortBy("url")->pluck("url")->toArray(),
                        $variant["sku"],
                        $this->processTabs($product),
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

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null, "product_import_enabled" => false]);
        }
        catch (\Exception $e)
        {
            Log::error("-- Error in " . self::SUPPLIER_NAME . ": " . $e->getMessage(), ["exception" => $e]);
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

    private function processTabs(array $product) {
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
        $documents = collect($product["digital_assets"] ?? [])
            ->mapWithKeys(fn ($d) => [Str::title($d["subtype"]) => $d["url"]])
            ->toArray();

        /**
         * each tab has name => content: array
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return [
            "Specyfikacja" => [["type" => "table", "content" => array_filter($specification ?? [])]],
            "Opakowanie" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            "Dokumenty do pobrania" => [["type" => "tiles", "content" => array_filter($documents ?? [])]],
        ];
    }
}
