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
    private const PRIMARY_KEY_STOCK = "IdTw";
    private const SKU_KEY = "KodKreskowy";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        $products = $this->getProductData();
        if ($sync->product_import_enabled)
            $params = $this->getParamData();
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockData();

        try
        {
            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids = array_merge(
                    $imported_ids,
                    collect($product["Warianty"] ?? $product["Variants"] ?? [])
                        ->map(fn ($v) => $this->getPrefix() . ($v[self::SKU_KEY] ?? $v["Barcode"] ?? null))
                        ->toArray(),
                );

                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]
                    || empty($product[self::SKU_KEY] ?? $product["Barcode"] ?? null)
                ) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY] ?? $product["Barcode"]]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product[self::PRIMARY_KEY]);

                foreach ($product["Warianty"] ?? $product["Variants"] ?? [] as $variant) {
                    Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $variant[self::SKU_KEY] ?? $product["Barcode"]]);

                    if ($sync->product_import_enabled) {
                        $this->saveProduct(
                            $this->getPrefix() . ($variant[self::SKU_KEY] ?? $variant["Barcode"]),
                            $product["Nazwa"] ?? $product["Name"],
                            $product["Opisy"]["PL"]["www"] ?? null,
                            $this->getPrefix() . ($product[self::SKU_KEY] ?? $product["Barcode"]),
                            null, // as_number($variant["CenaBazowa"]),
                            (isset($variant["Zdjecia"]))
                                ? collect($variant["Zdjecia"])->pluck("link")->toArray()
                                : collect($product["Photos"])->pluck("URL")->toArray(),
                            (isset($variant["Zdjecia"]))
                                ? collect($variant["Zdjecia"])->pluck("link")->toArray()
                                : collect($product["Photos"])->pluck("URL")->toArray(),
                            $variant[self::SKU_KEY] ?? $variant["Barcode"],
                            (isset($variant["Slowniki"]))
                                ? $this->processTabs($product, $variant, $params)
                                : null,
                            (isset($product["Kategorie"]))
                                ? implode(" | ", $product["Kategorie"]["KategorieB2B"])
                                : "Opakowania", // assuming english-labelled products are boxes
                            (isset($variant["Slowniki"]))
                                ? collect([
                                    $this->getParam($params, "sl_Kolor", $variant["Slowniki"]["sl_Kolor"]),
                                    $this->getParam($params, "sl_KolorFiltr", $variant["Slowniki"]["sl_KolorFiltr"])
                                ])
                                    ->filter()
                                    ->unique()
                                    ->join("/")
                                : null,
                            source: self::SUPPLIER_NAME,
                        );
                    }

                    if ($sync->stock_import_enabled) {
                        $stock = $stocks->firstWhere(self::PRIMARY_KEY_STOCK, $variant[self::PRIMARY_KEY]);
                        if ($stock) {
                            $next_delivery = collect($stock["Dostawy"])
                                ->sortBy("Data")
                                ->first();
                            $this->saveStock(
                                $this->getPrefix() . ($variant[self::SKU_KEY] ?? $variant["Barcode"]),
                                $stock["Stan"],
                                $next_delivery["Ilosc"] ?? null,
                                $next_delivery ? Carbon::parse($next_delivery["Data"]) : null
                            );
                        } else {
                            $this->saveStock($this->getPrefix() . ($variant[self::SKU_KEY] ?? $variant["Barcode"]), 0);
                        }
                    }
                }

                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress (step)", (++$counter / $total) * 100);
            }

            if ($sync->product_import_enabled) {
                $this->deleteUnsyncedProducts($sync, $imported_ids);
            }
            $this->reportSynchCount(self::SUPPLIER_NAME, $counter, $total);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "complete");
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY], "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
    }

    private function getProductData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling product data");
        $products = Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetProducts", [
                "lang" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect();
        Log::debug(self::SUPPLIER_NAME . "> --- found " . $products->count());

        Log::info(self::SUPPLIER_NAME . "> -- pulling packaging data");
        $products = $products->merge(
            Http::acceptJson()
                ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
                ->post(self::URL . "GetBoxes", [
                    "lang" => "pl",
                ])
                ->throwUnlessStatus(200)
                ->collect()
        );
        Log::debug(self::SUPPLIER_NAME . "> --- now at " . $products->count());

        return $products->sortBy(self::PRIMARY_KEY);
    }
    private function getStockData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling stock data");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetStock", [])
            ->throwUnlessStatus(200)
            ->collect();
    }
    private function getParamData(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling dictionaries");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetParams", [])
            ->throwUnlessStatus(200)
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
