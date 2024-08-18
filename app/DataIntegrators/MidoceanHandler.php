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
}
