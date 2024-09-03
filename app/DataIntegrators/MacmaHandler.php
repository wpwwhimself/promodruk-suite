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
    private const PRIMARY_KEY = "id";
    private const SKU_KEY = "code_full";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo()->sortBy(self::PRIMARY_KEY);

        try
        {
            $total = $products->count();
            if ($total == 0) {
                throw new \Exception("No products found, API is probably down or overworked");
            }

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > (int) $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY]]);

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
                            ->map(fn($i) => str_replace("/large", "/medium", $i))
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
                        as_number($stock["quantity_24h"]) + as_number($stock["quantity_37days"]),
                        as_number($stock["quantity_delivery"]),
                        Carbon::today()->addMonths(2)->firstOfMonth() // todo znaleźć
                    );
                    else $this->saveStock($this->getPrefix() . $product["code_full"], 0);
                }

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null]);
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY]]);
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

        $markings = ["Grupy i rozmiary znakwania" => "https://www.macma.pl/data/shopproducts/$product[id]/print-area/$product[code_full].pdf"];

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
}
