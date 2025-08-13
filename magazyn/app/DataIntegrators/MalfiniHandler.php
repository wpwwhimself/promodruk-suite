<?php

namespace App\DataIntegrators;

use App\Models\Stock;
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
        if ($this->limit_to_module == "marking") {
            $this->sync->addLog("complete", 1, "Synchronization unavailable for $this->limit_to_module");
            return;
        }

        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        [
            "ids" => $ids,
            "products" => $products,
            "stocks" => $stocks,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $ids->count();
        $this->imported_ids = [];

        foreach ($ids as [$sku, $external_id]) {
            if ($this->sync->current_module_data["current_external_id"] != null && $this->sync->current_module_data["current_external_id"] > $external_id) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("sku", "products"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            // if ($this->canProcessModule("marking")) {
            //     $this->prepareAndSaveMarkingData(compact(...));
            // }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($this->imported_ids);
                $this->imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($this->imported_ids);

        $this->reportSynchCount($counter, $total);
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        if ($this->limit_to_module) {
            $product = $stock = $marking = false;
            ${$this->limit_to_module} = true;
        }

        $products = ($product) ? $this->getProductData() : collect();
        $stocks = ($stock) ? $this->getStockData() : collect();
        // [$marking_labels, $marking_prices] = ($marking) ? $this->getMarkingData() : [collect(),collect()];

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                $p[self::SKU_KEY] ?? $p["productSizeCode"],
                $p[self::PRIMARY_KEY] ?? $p["productSizeCode"],
            ]);

        return compact(
            "ids",
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
            ->collect()
            ->sortBy("productSizeCode");

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
     * @param array $data sku, products
     */
    public function prepareAndSaveProductData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
        ] = $data;

        $product = $products->firstWhere(self::SKU_KEY, $sku);
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
            $ret[] = $this->saveProduct(
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

        return $ret;
    }

    /**
     * @param array $data sku, stocks
     */
    public function prepareAndSaveStockData(array $data): array
    {
        $ret = [];

        [
            "sku" => $sku,
            "stocks" => $stocks,
        ] = $data;

        $family_stocks = $stocks->filter(fn ($s) => Str::startsWith($s["productSizeCode"], $sku));

        foreach ($family_stocks as $stock) {
            $ret[] = $this->saveStock(
                $this->getPrefixedId($stock["productSizeCode"]),
                $stock["quantity"],
            );
        }

        return $ret;
    }

    /**
     * @param array $data ???
     */
    public function prepareAndSaveMarkingData(array $data): null
    {
        // [
        //     "product" => $product,
        // ] = $data;

        // ...

        // $this->deleteCachedUnsyncedMarkings();

        abort(501);
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
