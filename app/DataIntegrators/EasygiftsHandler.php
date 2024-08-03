<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasygiftsHandler extends ApiHandler
{
    private const URL = "https://www.easygifts.com.pl/data/webapi/pl/json/";
    private const SUPPLIER_NAME = "Easygifts";
    public function getPrefix(): string { return "EA"; }

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
            $products = $this->getProductInfo()->sortBy("ID");
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo()->sortBy("ID");

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product["ID"]) {
                    $counter++;
                    continue;
                }

                Log::debug("-- downloading product " . $product["CodeFull"]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["ID"]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $this->getPrefix() . $product["CodeFull"],
                        $product["Name"],
                        $product["Intro"],
                        $this->getPrefix() . $product["CodeShort"],
                        collect($product["Images"])->sort()->toArray(),
                        collect($product["Categories"])->map(fn ($cat) => collect($cat)->map(fn ($ccat,$i) => "$i > $ccat"))->flatten()->first()
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks->firstWhere("ID", $product["ID"]);
                    if ($stock) $this->saveStock(
                        $this->getPrefix() . $product["CodeFull"],
                        $stock["Quantity24h"] /* + $stock["Quantity37days"] */,
                        $stock["Quantity37days"],
                        Carbon::today()->addDays(3)
                    );
                    else $this->saveStock($this->getPrefix() . $product["CodeFull"], 0);
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
            ->get(self::URL . "stocks.json", [])
            ->collect();

        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        return $res;
    }

    private function getProductInfo()
    {
        $res = Http::acceptJson()
            ->get(self::URL . "offer.json", [])
            ->collect();

        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        return $res;
    }
}
