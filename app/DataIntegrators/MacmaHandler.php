<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MacmaHandler extends ApiHandler
{
    private const URL = "http://api.macma.pl/pl/";
    private const SUPPLIER_NAME = "Macma";
    public function getPrefix(): string { return "MC"; }

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $counter = 0;
        $total = 0;

        if ($sync->product_import_enabled)
            $products = $this->getProductInfo()->sortBy("id");
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo()->sortBy("id");

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product["id"]) {
                    $counter++;
                    continue;
                }

                Log::debug("-- downloading product " . $product["code_full"]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["id"]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $this->getPrefix() . $product["code_full"],
                        $product["name"],
                        $product["intro"],
                        $this->getPrefix() . $product["code_short"],
                        str_replace(",", ".", $product["price"]),
                        collect($product["images"])
                            ->sort()
                            ->toArray(),
                        collect($product["images"])
                            ->sort()
                            ->map(fn($i) => str_replace("/large", "/small", $i))
                            ->toArray(),
                        $product["code_full"],
                        $this->processTabs($product),
                        implode(
                            " > ",
                            collect(collect($product["categories"])->first())
                                ->pipe(fn($c) => ($c->first() == "null")
                                    ? []
                                    : [
                                        $c["name"],
                                        collect($c["subcategories"] ?? null)?->first()["name"] ?? null
                                    ]
                                )
                        ),
                        $product["color_name"],
                        downloadPhotos: true
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks->firstWhere("id", $product["id"]);
                    if ($stock) $this->saveStock(
                        $this->getPrefix() . $product["code_full"],
                        $stock["quantity_24h"],
                        $stock["quantity_37days"],
                        Carbon::today()->addDays(3)
                    );
                    else $this->saveStock($this->getPrefix() . $product["code_full"], 0);
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
        $res = Http::acceptJson()
            ->get(self::URL . env("MACMA_API_KEY") . "/stocks/json", []);

        return $res->collect();
    }

    private function getProductInfo(): Collection
    {
        $res = Http::acceptJson()
            ->get(self::URL . env("MACMA_API_KEY") . "/products/json", []);

        return $res->collect();
    }

    private function processTabs(array $product) {
        /**
         * each tab has name => content: array
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return [
            // "Specyfikacja" => [],
            // "Zdobienie" => [],
            // "Opakowanie" => [],
        ];
    }
}
