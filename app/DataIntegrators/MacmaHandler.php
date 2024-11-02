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
    #region constants
    private const URL = "http://api.macma.pl/pl/";
    private const SUPPLIER_NAME = "Macma";
    public function getPrefix(): string { return "MC"; }
    private const PRIMARY_KEY = "id";
    public const SKU_KEY = "code_full";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }
    #endregion

    #region auth
    public function authenticate(): void
    {
        // no auth required here
    }
    #endregion

    #region main
    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        [
            "products" => $products,
            "stocks" => $stocks,
        ] = $this->downloadData(
            $sync->product_import_enabled,
            $sync->stock_import_enabled,
            $sync->marking_import_enabled
        );

        try
        {
            $total = $products->count();
            if ($total == 0) {
                throw new \Exception("No products found, API is probably down or overworked");
            }
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids[] = $this->getPrefix() . $product[self::SKU_KEY];

                if ($sync->current_external_id != null && $sync->current_external_id > (int) $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product[self::PRIMARY_KEY]);

                if ($sync->product_import_enabled) {
                    $this->prepareAndSaveProductData(compact("product"));
                }

                if ($sync->stock_import_enabled) {
                    $this->prepareAndSaveStockData(compact("product", "stocks"));
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
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        $stocks = ($stock) ? $this->getStockInfo()->sortBy(self::PRIMARY_KEY) : collect();

        return compact(
            "products",
            "stocks",
        );
    }

    private function getStockInfo(): Collection
    {
        $res = Http::acceptJson()
            ->get(self::URL . env("MACMA_API_KEY") . "/stocks/json", [])
            ->throwUnlessStatus(200);

        return $res->collect();
    }

    private function getProductInfo(): Collection
    {
        $res = Http::acceptJson()
            ->get(self::URL . env("MACMA_API_KEY") . "/products/json", [])
            ->throwUnlessStatus(200);

        return $res->collect();
    }
    #endregion

    #region processing
    /**
     * @param array $data product
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "product" => $product,
        ] = $data;

        $this->saveProduct(
            $product[self::SKU_KEY],
            $product["name"],
            $product["intro"],
            $product["code_short"],
            str_replace(",", ".", $product["price"]),
            collect($product["images"])
                ->sort()
                ->toArray(),
            collect($product["images"])
                ->sort()
                ->map(fn($i) => str_replace("/large", "/medium", $i))
                ->toArray(),
            $this->getPrefix(),
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
            downloadPhotos: true,
            source: self::SUPPLIER_NAME,
        );
    }

    /**
     * @param array $data product, stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "product" => $product,
            "stocks" => $stocks,
        ] = $data;

        $stock = $stocks->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY]);
        if ($stock) $this->saveStock(
            $this->getPrefixedId($product[self::SKU_KEY]),
            $stock["stan_magazynowy"],
            $stock["ilosc_dostawy"],
            isset($stock["data_dostawy"]) ? Carbon::parse($stock["data_dostawy"]) : null
        );
        else $this->saveStock($this->getPrefixedId($product[self::SKU_KEY]), 0);
    }

    /**
     * @param array $data ???
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        // unavailable yet
    }

    private function processTabs(array $product) {
        $specification = collect([
            "markgroups" => "Grupy znakowania",
            "marking_size" => "Rozmiar znakowania",
            "materials" => "Materiał",
            "size" => "Rozmiar produktu",
            "weight" => "Waga",
            "color_name" => "Kolor",
            "country" => "Kraj pochodzenia",
            "brand" => "Marka",
        ])
            ->mapWithKeys(fn($label, $item) => [
                $label => is_array($product[$item])
                    ? collect($product[$item])
                        ->sortBy("id")
                        ->pluck("name")
                        ->join($item == "markgroups" ? "\n" : ", ")
                    : $product[$item]
            ])
            ->toArray();

        $packing = collect([
            "packages" => "Opakowanie",
        ])
            ->mapWithKeys(fn($label, $item) => [
                $label => is_array($product[$item])
                    ? collect($product[$item])
                        ->sortBy("id")
                        ->pluck("name")
                        ->join($item == "markgroups" ? "\n" : ", ")
                    : $product[$item]
            ])
            ->toArray();

        $markings = ["Grupy i rozmiary znakowania (pobierz PDF)" => "https://www.macma.pl/data/shopproducts/$product[id]/print-area/$product[code_full].pdf"];

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
                "name" => "Opakowanie",
                "cells" => [["type" => "table", "content" => $packing]],
            ],
            [
                "name" => "Znakowanie",
                "cells" => [["type" => "tiles", "content" => $markings]],
            ],
        ]);
    }
    #endregion
}
