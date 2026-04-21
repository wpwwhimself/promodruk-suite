<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class InspirionHandler extends ApiHandler
{
    #region constants
    private const URL = "https://leoapi.inspirion.eu/";
    private const SUPPLIER_NAME = "Inspirion";
    public function getPrefix(): string { return "IN"; }
    private const PRIMARY_KEY = "article_id";
    public const SKU_KEY = "sku";
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
        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        [
            "ids" => $ids,
            "products" => $products,
            "prices" => $prices,
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
                $this->prepareAndSaveProductData(compact("sku", "products", "prices"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            // if ($this->canProcessModule("marking")) {
            //     $this->prepareAndSaveMarkingData(compact("sku", "products", "markings"));
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

        [$products, $prices] = ($product) ? $this->getProductInfo() : [collect(), collect()];
        $stocks = ($stock) ? $this->getStockInfo() : collect();

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                $p[self::SKU_KEY] ?? $p[self::PRIMARY_KEY],
                $p[self::PRIMARY_KEY] ?? $p[self::SKU_KEY],
            ]);

        return compact(
            "ids",
            "products",
            "prices",
            "stocks",
        );
    }

    private function getStockInfo(): Collection
    {
        $stocks = Http::acceptJson()
            ->withHeader("X-Gateway-API-Key", env("INSPIRION_API_KEY"))
            ->get(self::URL . "api/v1/stock", [])
            ->throwUnlessStatus(200)
            ->collect()
            ->sortBy(self::PRIMARY_KEY);

        return $stocks;
    }

    private function getProductInfo(): array
    {
        $products = Http::acceptJson()
            ->withHeader("X-Gateway-API-Key", env("INSPIRION_API_KEY"))
            ->get(self::URL . "api/v2/products", [
                "language" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect()
            ->sortBy(self::SKU_KEY);

        $prices = Http::acceptJson()
            ->withHeader("X-Gateway-API-Key", env("INSPIRION_API_KEY"))
            ->get(self::URL . "api/v2/pricelist", [])
            ->throwUnlessStatus(200)
            ->collect();

        return [$products, $prices];
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products, prices
     */
    public function prepareAndSaveProductData(array $data): array
    {
        [
            "sku" => $sku,
            "products" => $products,
            "prices" => $prices,
        ] = $data;

        $product = $products->firstWhere(fn ($p) => $p[self::SKU_KEY] == $sku);
        $variants = $product["variants"];

        $imported_ids = [];
        $i = 0;

        foreach ($variants as $variant_i => $variant) {
            $family_sku = $this->getPrefixedId($product[self::SKU_KEY]);
            $variant_sku = $this->getPrefixedId($variant[self::PRIMARY_KEY]);

            $this->sync->addLog("in progress", 3, "saving product variant ".$variant_sku." (".$family_sku.")");
            $ret[] = $this->saveProduct(
                $variant[self::PRIMARY_KEY],
                $variant[self::PRIMARY_KEY],
                $product["name"],
                $product["description"],
                $this->getPrefixedId($product[self::SKU_KEY]),
                as_number(collect($prices->firstWhere(self::PRIMARY_KEY, $variant[self::PRIMARY_KEY])["pricelist"] ?? null)
                    ->first()["catalog_price"] ?? null
                ),
                collect($variant["images_300"])->sortBy("image_url")->pluck("image_url")->toArray(),
                collect($variant["images"])->sortBy("image_url")->pluck("image_url")->toArray(),
                $this->getPrefix(),
                $this->processTabs($product, $variant),
                implode(" > ", array_filter([$product["categories"]["parent"], $product["categories"]["child"]])),
                $variant["colour"],
                source: self::SUPPLIER_NAME,
            );

            $imported_ids[] = $variant_sku;
        }

        // tally imported IDs
        $this->imported_ids = array_merge($this->imported_ids, $imported_ids);

        return $ret;
    }

    /**
     * @param array $data sku, stocks
     */
    public function prepareAndSaveStockData(array $data): Stock
    {
        [
            "sku" => $sku,
            "stocks" => $stocks,
        ] = $data;

        $stock = $stocks->firstWhere(fn ($pr) => $pr[self::PRIMARY_KEY] == $sku);
        $earliest_inventory_arrival = collect($stock["earliest_inventory_arrival"])->first();

        if ($stock) return $this->saveStock(
            $this->getPrefixedId($sku),
            $stock["stock_poland"],
            $earliest_inventory_arrival["arrival_quantity"] ?? null,
            $earliest_inventory_arrival ? Carbon::parse($earliest_inventory_arrival["arrival_date"]) : null
        );
        else return $this->saveStock($this->getPrefixedId($sku), 0);
    }

    /**
     * @param array $data ...
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        // disabled

        return null;
    }

    private function processTabs(array $product, array $variant) {
        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification = [
            "Wymiar (Produkt)" => $this->processSize($variant["size"]),
            "Waga netto" => implode (" ", [$variant["weight"]["net"], $variant["weight"]["unit"]]),
            "Waga brutto" => implode (" ", [$variant["weight"]["gross"], $variant["weight"]["unit"]]),
            "Kraj pochodzenia" => $product["origin_country"],
            "Marka" => $product["brand"],
        ];

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
        ]);
    }

    private function processSize(array $size_data): string
    {
        return implode(" x ", [
            implode(" ", [$size_data["length"], $size_data["length_unit"]]),
            implode(" ", [$size_data["width"], $size_data["width_unit"]]),
            implode(" ", [$size_data["height"], $size_data["height_unit"]]),
        ]);
    }
    #endregion
}
