<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PARHandler extends ApiHandler
{
    private const URL = "https://www.par.com.pl/api/";
    private const SUPPLIER_NAME = "PAR";
    public function getPrefix(): string { return "R"; }

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
            Log::debug("-- pulling product data. This may take a while...");
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

                Log::debug("-- downloading product " . $product["kod"]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["id"]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product["kod"],
                        $product["nazwa"],
                        $product["opis"],
                        Str::beforeLast($product["kod"], "."),
                        collect($product["zdjecia"])->sortBy("zdjecie")->map(fn($i) => "https://www.par.com.pl". $i["zdjecie"])->toArray(),
                        collect($product["kategorie"])->first()["name"]
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks->firstWhere("id", $product["id"]);
                    if ($stock) $this->saveStock(
                        $product["kod"],
                        $stock["stan_magazynowy"],
                        $stock["ilosc_dostawy"],
                        isset($stock["data_dostawy"]) ? Carbon::parse($stock["data_dostawy"]) : null
                    );
                    else $this->saveStock($product["kod"], 0);
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
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "stocks.json", []);

        return $res->collect("products")
            ->map(fn($i) => $i["product"]);
    }

    private function getProductInfo(): Collection
    {
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "products", []);

        return $res->collect("products")
            ->map(fn($i) => $i["product"]);
    }
}
