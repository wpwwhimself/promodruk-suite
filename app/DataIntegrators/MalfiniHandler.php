<?php

namespace App\DataIntegrators;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MalfiniHandler extends ApiHandler
{
    #region constants
    private const URL = "https://api.malfini.com/api/v4/";
    private const SUPPLIER_NAME = "Malfini";
    public function getPrefix(): string { return "MF"; }
    private const PRIMARY_KEY = "code";
    public const SKU_KEY = "code";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }

    private array $imported_ids;
    #endregion

    #region auth
    public function authenticate(): void
    {
        if (empty(session("malfini_token")))
            $this->prepareToken();

        $res = $this->testRequest(self::URL);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = $this->testRequest(self::URL);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
        }
    }

    private function testRequest($url)
    {
        return Http::acceptJson()
            ->withToken(session("malfini_token"))
            ->get($url . "payment-method");
    }

    private function prepareToken()
    {
        $res = Http::acceptJson()
            ->post(self::URL . "api-auth/login", [
                "username" => env("MALFINI_API_LOGIN"),
                "password" => env("MALFINI_API_PASSWORD"),
            ])
            ->throwUnlessStatus(200);
        session([
            "malfini_token" => $res->json("access_token"),
            "malfini_refresh_token" => $res->json("refresh_token"),
        ]);
    }

    private function refreshToken()
    {
        $res = Http::acceptJson()
            ->withToken(session("malfini_token"))
            ->post(self::URL . "api-auth/refresh", [
                "refreshToken" => session("malfini_refresh_token"),
            ]);
        session("malfini_token", $res->json("access"));
    }
    #endregion

    #region main
    public function downloadAndStoreAllProductData(): void
    {
        $this->sync->addLog("pending", 1, "Synchronization started");

        $counter = 0;
        $total = 0;

        [
            "products" => $products,
            "stocks" => $stocks,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $products->count();
        $this->imported_ids = [];

        foreach ($products as $product) {
            if ($this->sync->current_external_id != null && $this->sync->current_external_id > $product[self::PRIMARY_KEY]) {
                $counter++;
                continue;
            }

            $family_stocks = $stocks->filter(fn ($s) => Str::startsWith($s["productSizeCode"], $product[self::PRIMARY_KEY]));

            $this->sync->addLog("in progress", 2, "Downloading product: ".$product[self::PRIMARY_KEY], $product[self::PRIMARY_KEY]);

            if ($this->sync->product_import_enabled) {
                $this->prepareAndSaveProductData(compact("product"));
            }

            if ($this->sync->stock_import_enabled) {
                $this->prepareAndSaveStockData(compact("family_stocks"));
            }

            // if ($this->sync->marking_import_enabled) {
            //     $this->prepareAndSaveMarkingData(compact("product", "marking_labels", "marking_prices"));
            // }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->sync->product_import_enabled) $this->deleteUnsyncedProducts($this->imported_ids);
                $this->imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->sync->product_import_enabled) $this->deleteUnsyncedProducts($this->imported_ids);

        $this->reportSynchCount($counter, $total);
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        $products = ($product) ? $this->getProductData() : collect();
        $stocks = ($stock) ? $this->getStockData() : collect();
        // [$marking_labels, $marking_prices] = ($marking) ? $this->getMarkingData() : [collect(),collect()];

        return compact(
            "products",
            "stocks",
        );
    }

    private function getProductData(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling products data...");
        $data = Http::acceptJson()
            ->withToken(session("malfini_token"))
            ->timeout(2*60)
            ->get(self::URL . "product", [
                "language" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect()
            ->sortBy(self::PRIMARY_KEY);

        return $data;
    }

    private function getStockData(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling stock data...");
        $data = Http::acceptJson()
            ->withToken(session("malfini_token"))
            ->get(self::URL . "product/availabilities")
            ->throwUnlessStatus(200)
            ->collect();

        return $data;
    }

    private function getMarkingData(): array
    {
        // omitted
        return [];
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

        $variants = $product["variants"];

        $imported_ids = [];
        $i = 0;

        foreach ($variants as $variant) {
            $prepared_sku = $variant[self::PRIMARY_KEY];

            $description = implode("", [
                "<p>$product[description]</p>",
                "<p><em>$product[specification]</em></p>",
                "<ul>"
                    .collect($variant["attributes"])
                        ->map(fn ($attr) => "<li><strong>$attr[title]:</strong> $attr[text]</strong></li>")
                        ->join("")
                ."</ul>",
            ]);

            $this->sync->addLog("in progress", 3, "saving product variant ".$prepared_sku."(".($i++ + 1)."/".count($variants).")", $product[self::PRIMARY_KEY]);
            $this->saveProduct(
                $this->getPrefixedId($prepared_sku),
                $prepared_sku,
                $product["name"],
                $description,
                $this->getPrefixedId($product[self::PRIMARY_KEY]),
                null, // disabled
                collect($variant["images"])
                    ->sortBy("viewCode")
                    ->pluck("link")
                    ->toArray(),
                collect($variant["images"])
                    ->sortBy("viewCode")
                    ->pluck("link")
                    ->toArray(),
                $this->getPrefix(),
                $this->processTabs($product),
                $product["categoryName"],
                $variant["name"],
                source: self::SUPPLIER_NAME,
                sizes: collect($variant["nomenclatures"])
                    ->map(fn($v) => [
                        "size_name" => $v["sizeName"],
                        "size_code" => $v["productSizeCode"],
                        "full_sku" => $this->getPrefixedId($v["productSizeCode"]),
                    ])
                    ->toArray(),
                subtitle: $product["subtitle"],
            );

            $imported_ids[] = $prepared_sku;
        }

        // tally imported IDs
        $this->imported_ids = array_merge($this->imported_ids, $imported_ids);
    }

    /**
     * @param array $data family_stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "family_stocks" => $family_stocks,
        ] = $data;

        foreach ($family_stocks as $stock) {
            $this->saveStock(
                $this->getPrefixedId($stock["productSizeCode"]),
                $stock["quantity"],
            );
        }
    }

    /**
     * @param array $data ???
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        // [
        //     "product" => $product,
        // ] = $data;

        // ...

        // $this->deleteCachedUnsyncedMarkings();
    }

    private function processTabs(array $product) {
        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return array_filter([
            [
                "name" => "Do pobrania",
                "cells" => [["type" => "tiles", "content" => [
                    "Tabela rozmiarów" => $product["sizeChartPdf"],
                    "Karta produktu" => $product["productCardPdf"],
                ]]],
            ],
        ]);
    }
    #endregion
}
