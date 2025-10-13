<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class USBSystemHandler extends ApiHandler
{
    #region constants
    private const FILE_NAME = "usb-system-products.xml";
    private const SUPPLIER_NAME = "USBSystem";
    public function getPrefix(): string { return "USBS"; }
    private const PRIMARY_KEY = "custom_id"; // this supplier doesn't have codes
    public const SKU_KEY = "custom_id"; // this supplier doesn't have skus
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

        // supplier doesn't have SKUs or IDs, so those have to be created now
        $products = $products->map(function ($p, $i) {
            $ret = $p;
            $p->addChild("custom_id", $i);
            return $ret;
        });

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

        $products = collect((new SimpleXMLElement($data))->xpath("//root/item"))
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
        $colors = explode(", ", (string) $product->product_attribute_kolor);

        foreach ($colors as $color_index => $color_name) {
            $prepared_sku_with_index = implode("-", array_filter([
                $prepared_sku,
                $color_index + 1,
            ]));
            $ret[] = $this->saveProduct(
                $prepared_sku_with_index,
                $product->{self::PRIMARY_KEY},
                (string) $product->name,
                (string) $product->description,
                $prepared_sku,
                null,
                [[], [(string) $product->image_url]],
                [[], [(string) $product->image_url]],
                $this->getPrefix(),
                null,
                collect([
                    (string) $product->categories,
                    (string) $product->product_attribute_katalog,
                ])
                    ->filter()
                    ->join(" > "),
                $color_name,
                source: self::SUPPLIER_NAME,
                specification: collect([
                    "Materiał" => (string) $product->product_attribute_material,
                    "Wymiary" => (string) $product->product_attribute_wymiary,
                    "Powierzchnia logo" => (string) $product->product_attribute_powierzchnia_logo,
                    "Znakowanie i wykończenie" => (string) $product->product_attribute_znakowanie,
                    "Funkcja OTG" => (string) $product->product_attribute_otg,
                    "Typ złącza USB" => (string) $product->product_attribute_typ_zlacza,
                ])->filter()->toArray(),
            );

            if (Str::contains((string) $product->product_attribute_katalog, "stock", true)) {
                $this->saveStock(
                    $prepared_sku_with_index,
                    null,
                );
            } else {
                Stock::find($prepared_sku_with_index)?->delete();
            }
        }

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
