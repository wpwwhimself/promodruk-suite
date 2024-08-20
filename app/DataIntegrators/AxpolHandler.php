<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AxpolHandler extends ApiHandler
{
    private const URL = "https://axpol.com.pl/api/b2b-api/";
    private const SUPPLIER_NAME = "Axpol";
    public function getPrefix(): array { return ["V", "P", "T"]; }
    private const USER_AGENT = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0. 2272.118 Safari/537.36";

    public function authenticate(): void
    {
        $res = Http::acceptJson()
            ->withUserAgent(self::USER_AGENT)
            ->asForm()
            ->post(self::URL . "", [
                "method" => "Customer.Login",
                "key" => env("AXPOL_API_SECRET"),
                "params[username]" => env("AXPOL_API_LOGIN"),
                "params[password]" => env("AXPOL_API_PASSWORD"),
            ])
            ->collect("data");

        session([
            "axpol_uid" => $res["uid"],
            "axpol_token" => $res["jwt"],
        ]);
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $counter = 0;
        $total = 0;

        Log::debug("-- pulling product data. This may take a while...");
        $products = $this->getProductInfo()->sortBy("productId");
        Log::debug("-- fetched products: " . $products->count());

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product["productId"]) {
                    $counter++;
                    continue;
                }

                Log::debug("-- downloading product " . $product["CodeERP"]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["productId"]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product["CodeERP"],
                        $product["TitlePL"],
                        $product["DescriptionPL"],
                        Str::beforeLast($product["CodeERP"], "."),
                        $product["NetPricePLN"],
                        collect($product["Foto"])->sort()->map(fn($i) => "https://axpol.com.pl/files/fotov/". $i)->toArray(),
                        collect($product["Foto"])->sort()->map(fn($i) => "https://axpol.com.pl/files/fotos/". $i)->toArray(),
                        $product["CodeERP"],
                        $this->processTabs($product),
                        implode(" > ", [$product["MainCategoryPL"], $product["SubCategoryPL"]]),
                        $product["ColorPL"]
                    );
                }

                if ($sync->stock_import_enabled) {
                    $this->saveStock(
                        $product["CodeERP"],
                        intval($product["InStock"]),
                        intval($product["nextDelivery"]),
                        Carbon::today()->addMonths(2)->firstOfMonth() // todo znaleźć
                    );
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

    private function getProductInfo(): Collection
    {
        $res = Http::acceptJson()
            ->withUserAgent(self::USER_AGENT)
            ->withToken(session("axpol_token"))
            ->timeout(300)
            ->get(self::URL . "", [
                "key" => env("AXPOL_API_SECRET"),
                "uid" => session("axpol_uid"),
                "method" => "Product.List",
                "params[date]" => "1970-01-01 00:00:00",
                "params[limit]" => 9999,
            ]);

        return $res->collect("data")
            ->filter(fn($p) => Str::startsWith($p["CodeERP"], $this->getPrefix()))
            ->filter(fn($p) => !Str::contains($p["TitlePL"], "test", true));
    }

    private function processTabs(array $product) {
        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return [
            // [
            //     "name" => "Specyfikacja",
            //     "cells" => [],
            // ],
            // [
            //     "name" => "Zdobienie",
            //     "cells" => [],
            // ],
            // [
            //     "name" => "Opakowanie",
            //     "cells" => [],
            // ],
        ];
    }
}
