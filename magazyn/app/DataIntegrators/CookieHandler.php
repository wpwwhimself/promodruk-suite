<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class CookieHandler extends ApiHandler
{
    #region constants
    private const FILE_NAME = "cookie-produkty.xml";
    private const SUPPLIER_NAME = "Cookie";
    public function getPrefix(): string { return "CK"; }
    private const PRIMARY_KEY = "id";
    public const SKU_KEY = "id";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }

    private array $imported_ids;
    #endregion

    #region auth
    public function authenticate(): void
    {
        // no auth required here
    }
    #endregion

    #region main
    public function downloadAndStoreAllProductData(): void
    {
        if ($this->limit_to_module == "marking" || $this->limit_to_module == "stock") {
            $this->sync->addLog("complete", 1, "Synchronization unavailable for $this->limit_to_module");
            return;
        }

        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        [
            "ids" => $ids,
            "products" => $products,
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

            // if ($this->canProcessModule("stock")) {
            //     $this->prepareAndSaveStockData(compact("sku", "stocks"));
            // }

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

        $products = ($product) ? $this->getProductInfo() : collect();

        $ids = collect([ $products ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                $p->{self::SKU_KEY},
                $p->{self::PRIMARY_KEY},
            ]);

        return compact(
            "ids",
            "products",
        );
    }

    private function getProductInfo(): Collection
    {
        $data = Storage::disk("public")->get("integrators/" . self::FILE_NAME);
        if (!$data) {
            throw new \Error("Source file not found");
        }

        $products = collect((new SimpleXMLElement($data))->xpath("//data/post"))
            ->sort(fn ($a, $b) => (int) $a->{self::PRIMARY_KEY} <=> (int) $b->{self::PRIMARY_KEY});

        return $products;
    }

    private function getStockInfo(): void
    {
        //
    }

    private function getMarkingInfo(): void
    {
        //
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

        $product = $products->firstWhere(fn ($p) => (string) $p->{self::SKU_KEY} == $sku);
        if (empty($product)) {
            $this->sync->addLog("in progress (step)", 2, "Product missing");
            return null;
        }

        $imported_ids = [];

        $prepared_sku = $this->getPrefixedId($product->{self::SKU_KEY});

        $ret[] = $this->saveProduct(
            $prepared_sku,
            $product->{self::PRIMARY_KEY},
            (string) $product->Title,
            Str::of((string) $product->Content)
                ->replace("http://nowa.cookie.com.pl", "https://cookie.com.pl"),
            $prepared_sku,
            null,
            [(string) $product->ImageFeatured],
            [(string) $product->ImageFeatured],
            $this->getPrefix(),
            null,
            (string) $product->Kategorieproduktw,
            null,
            source: self::SUPPLIER_NAME,
        );

        $imported_ids[] = $product->{self::PRIMARY_KEY};

        // tally imported IDs
        $this->imported_ids = array_merge($this->imported_ids, $imported_ids);

        return $ret;
    }

    /**
     * @param array $data sku, stocks
     */
    public function prepareAndSaveStockData(array $data): null
    {
        abort(501);
    }

    /**
     * @param array $data ...
     */
    public function prepareAndSaveMarkingData(array $data): null
    {
        $this->deleteCachedUnsyncedMarkings();

        abort(501);
    }

    private function processTabs(SimpleXMLElement $product) {
        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return array_filter([
            // [
            //     "name" => "Kluczowe funkcje",
            //     "cells" => [["type" => "table", "content" => array_filter($key_features ?? [])]],

            // ],
            [
                //
            ],
        ]);
    }
    #endregion
}
