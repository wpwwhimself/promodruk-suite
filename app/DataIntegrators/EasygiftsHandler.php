<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasygiftsHandler extends ApiHandler
{
    private const URL = "https://www.easygifts.com.pl/data/webapi/pl/json/";
    private const SUPPLIER_NAME = "Easygifts";
    public function getPrefix(): string { return "EA"; }
    private const PRIMARY_KEY = "ID";
    private const SKU_KEY = "CodeFull";

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

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $this->getPrefix() . $product["CodeFull"],
                        $product["Name"],
                        $product["Intro"],
                        $this->getPrefix() . $product["CodeShort"],
                        $product["Price"],
                        collect($product["Images"])->sort()->toArray(),
                        collect($product["Images"])->sort()->map(fn($img) => Str::replaceFirst('large-', 'small-', $img))->toArray(),
                        $product["CodeFull"],
                        $this->processTabs($product),
                        collect($product["Categories"])->map(fn ($cat) => collect($cat)->map(fn ($ccat,$i) => "$i > $ccat"))->flatten()->first(),
                        $product["ColorName"]
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
            ->get(self::URL . "stocks.json", [])
            ->collect();

        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        return $res;
    }

    private function getProductInfo()
    {
        // prices
        $prices = Http::acceptJson()
            ->get(self::URL . "prices.json", [])
            ->collect();
        $header = $prices[0];
        $prices = $prices->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        // products
        $res = Http::acceptJson()
            ->get(self::URL . "offer.json", [])
            ->collect();
        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row))
            ->map(fn($row) => [...$row, ...$prices->firstWhere("ID", $row["ID"])]);

        return $res;
    }

    private function processTabs(array $product) {
        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification_fields = [
            "Size" => "Rozmiar produktu",
            "Materials" => "Materiał",
            "OriginCountry" => "Kraj pochodzenia",
            "Brand" => "Marka",
            "Weight" => "Waga",
            "ColorName" => "Kolor",
        ];
        $specification = [];
        foreach ($specification_fields as $item => $label) {
            $specification[$label] = is_array($product[$item])
                ? implode(", ", $product[$item])
                : $product[$item];
        }

        //! packaging
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $packaging_fields = [
            "Packages" => "Opakowanie",
            "PackSmall" => "Małe opakowanie (szt.)",
            "PackLarge" => "Duże opakowanie (szt.)",
        ];
        $packaging = [];
        foreach ($packaging_fields as $item => $label) {
            $packaging[$label] = is_array($product[$item])
                ? implode(", ", $product[$item])
                : $product[$item];
        }

        //! markings
        $markings["Grupy i rozmiary znakowania"] = implode("\n", $product["MarkGroups"]);

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
                "cells" => [["type" => "table", "content" => array_filter($specification ?? [])]],

            ],
            [
                "name" => "Opakowanie",
                "cells" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            ],
            [
                "name" => "Znakowanie",
                "cells" => [["type" => "table", "content" => array_filter($markings ?? [])]],
            ],
        ]);
    }
}
