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
    private const PRIMARY_KEY = "productId";
    private const SKU_KEY = "CodeERP";
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
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now(), "synch_status" => 0]);

        $counter = 0;
        $total = 0;

        Log::info(self::SUPPLIER_NAME . "> -- pulling products data. This may take a while...");
        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        Log::info(self::SUPPLIER_NAME . "> -- pulling markings data. This may take a while...");
        $markings = $this->getMarkingInfo()->sortBy(self::PRIMARY_KEY);

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY], "synch_status" => 1]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product["CodeERP"],
                        $product["TitlePL"],
                        $product["DescriptionPL"],
                        Str::beforeLast($product["CodeERP"], "-"),
                        as_number($product["NetPricePLN"]),
                        collect($product["Foto"])->sort()->map(fn($file, $i) => "https://axpol.com.pl/files/" . ($i == 0 ? "fotov" : "foto_add_view") . "/". $file)->toArray(),
                        collect($product["Foto"])->sort()->map(fn($file, $i) => "https://axpol.com.pl/files/" . ($i == 0 ? "fotom" : "foto_add_medium") . "/". $file)->toArray(),
                        $product["CodeERP"],
                        $this->processTabs($product, $markings[$product["productId"]]),
                        implode(" > ", [$product["MainCategoryPL"], $product["SubCategoryPL"]]),
                        $product["ColorPL"],
                        source: self::SUPPLIER_NAME,
                    );
                }

                if ($sync->stock_import_enabled) {
                    $this->saveStock(
                        $product["CodeERP"],
                        as_number($product["InStock"]) + ($product["Days"] == "1 - 2" ? as_number($product["onOrder"]) : 0),
                        as_number($product["nextDelivery"]),
                        Carbon::today()->addMonths(2)->firstOfMonth() // todo znaleźć
                    );
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
    private function getMarkingInfo(): Collection
    {
        $res = Http::acceptJson()
            ->withUserAgent(self::USER_AGENT)
            ->withToken(session("axpol_token"))
            ->timeout(300)
            ->get(self::URL . "", [
                "key" => env("AXPOL_API_SECRET"),
                "uid" => session("axpol_uid"),
                "method" => "Printing.List",
                "params[date]" => "1970-01-01 00:00:00",
                "params[limit]" => 9999,
            ]);

        return $res->collect("data")
            ->filter(fn($p) => Str::startsWith($p["CodeERP"], $this->getPrefix()));
    }

    private function processTabs(array $product, array $marking) {
        $specification = collect([
            "Dimensions" => "Wymiary",
            "MaterialPL" => "Materiał",
            "Page" => "Strona w katalogu",
            "ColorPL" => "Kolor",
            "Film" => "Film",
            "Video360" => "Video360",

            "CountryOfOrigin" => "Kraj pochodzenia",
            "CustomCode" => "Kod PCN",
            "ItemWeightG" => "Waga produktu (g)",
            "EAN" => "EAN",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $product[$item] ?? null])
            ->toArray();

        $packing = collect([
            "IndividualPacking" => "Pakowanie indywidualne",
            "ExportCtnQty" => "Ilość w kartonie zbiorczym",
            "CtnDimensions" => "Wymiary kartonu zbiorczego",
            "CtnWeightKG" => "Waga kartonu zbiorczego",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $product[$item] ?? null])
            ->toArray();

        $marking_data = collect($marking["Print"])
            ->filter(fn($p) => !empty($p["Position"]))
            ->mapWithKeys(fn($variant) => [$variant["Position"] => implode("\n", [
                $variant["Size"],
                implode(", ", $variant["Technique"] ?? []),
            ])])
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
                "cells" => [["type" => "table", "content" => $specification]],
            ],
            [
                "name" => "Pakowanie",
                "cells" => [["type" => "table", "content" => $packing]],
            ],
            !$marking_data ? null : [
                "name" => "Znakowanie",
                "cells" => [
                    ["type" => "table", "content" => $marking_data],
                    ["type" => "tiles", "content" => ["Print info" => "https://axpol.com.pl/files/image/print_info_pl.jpg"]],
                ]
            ],
        ]);
    }
}
