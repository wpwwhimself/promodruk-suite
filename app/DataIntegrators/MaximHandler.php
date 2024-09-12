<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaximHandler extends ApiHandler
{
    private const URL = "https://api.maxim.com.pl/Api/";
    private const SUPPLIER_NAME = "Maxim";
    public function getPrefix(): string { return "MX"; }
    private const PRIMARY_KEY = "IdTW";
    private const SKU_KEY = "KodKreskowy";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now(), "synch_status" => 0]);

        $counter = 0;
        $total = 0;

        if ($sync->product_import_enabled) {
            $products = $this->getProductData();
            $params = $this->getParamData();
        }
        if ($sync->stock_import_enabled) {
            $stocks = $this->getStockData();
        }

        Log::info(self::SUPPLIER_NAME . "> -- ready!");

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]
                    || empty($product[self::SKU_KEY])
                ) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY], "synch_status" => 1]);

                foreach ($product["Warianty"] as $variant) {
                    Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $variant[self::SKU_KEY]]);
                    ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY], "synch_status" => 1]);

                    if ($sync->product_import_enabled)
                    $this->saveProduct(
                        $this->getPrefix() . $variant[self::SKU_KEY],
                        $variant["Nazwa"],
                        $product["Opisy"]["PL"]["www"] ?? null,
                        $this->getPrefix() . $product[self::SKU_KEY],
                        as_number($variant["CenaBazowa"]),
                        collect($variant["Zdjecia"])->pluck("link")->toArray(),
                        collect($variant["Zdjecia"])->pluck("link")->toArray(),
                        $variant[self::SKU_KEY],
                        $this->processTabs($product, $variant, $params),
                        implode(" | ", $product["Kategorie"]["KategorieB2B"]),
                        collect([
                            $this->getParam($params, "sl_Kolor", $variant["Slowniki"]["sl_Kolor"]),
                            $this->getParam($params, "sl_KolorFiltr", $variant["Slowniki"]["sl_KolorFiltr"])
                        ])
                            ->filter()
                            ->unique()
                            ->join("/"),
                        source: self::SUPPLIER_NAME,
                    );

                    if ($sync->stock_import_enabled) {
                        $stock = $stocks->firstWhere(self::PRIMARY_KEY, $variant[self::PRIMARY_KEY]);
                        if ($stock) {
                            $next_delivery = collect($stock["Dostawy"])
                                ->sortBy("Data")
                                ->first();
                            $this->saveStock(
                                $this->getPrefix() . $variant[self::SKU_KEY],
                                $stock["Stan"],
                                $next_delivery["Ilosc"] ?? null,
                                $next_delivery ? Carbon::parse($next_delivery["Data"]) : null
                            );
                        } else {
                            $this->saveStock($this->getPrefix() . $variant[self::SKU_KEY], 0);
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

    private function getProductData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling product data");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetProducts", [])
            ->collect()
            ->sortBy(self::PRIMARY_KEY);
    }
    private function getStockData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling stock data");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetStock", [])
            ->collect();
    }
    private function getParamData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling dictionaries");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetParams", [])
            ->collect();
    }
    private function getParam(Collection $params, string $dictionary, ?int $key): string | null
    {
        if (empty($key)) return null;
        return $params[$dictionary][$key]["description"] ?? null;
    }
    // private function getMarkingData(int $page): Collection
    // {
    //     return collect();
    // }
    // private function getCategoryData(): array
    // {
    //     return [];
    // }

    private function processTabs(array $product, array $variant, Collection $params) {
        //! specification
        $specification = collect([
            "Pojemnosc" => "Pojemnosc [ml]",
            "Wysokosc" => "Wysokość [mm]",
            "Srednica" => "Średnica [mm]",
            "Waga" => "Waga [g]",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $variant[$item] ?? null])
            ->pipe(fn($col) => $col->merge([
                "Materiał" => $this->getParam($params, "sl_Material", $variant["Slowniki"]["sl_Material"]),
            ]))
            ->toArray();

        //! packaging
        // tbd

        //! markings
        //! no marking data found in API

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
                "cells" => [["type" => "table", "content" => array_filter($specification ?? [])]]
            ],
            // [
            //     "name" => "Pakowanie",
            //     "cells" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            // ],
            [
                "name" => "Znakowanie",
                "cells" => [["type" => "tiles", "content" => ["Standardowe powierzchnie nadruku" => "https://legacy.maxim.com.pl/pdf/".Str::replace("M", "M_", $product[self::SKU_KEY])."_1.pdf"]]],
            ],
        ]);
    }
}
