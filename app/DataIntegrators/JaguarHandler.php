<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class JaguarHandler extends ApiHandler
{
    #region constants
    private const URL = "https://jaguargift.com/pl/xml/";
    private const SUPPLIER_NAME = "Jaguar";
    public function getPrefix(): string { return "JAG"; }
    private const PRIMARY_KEY = "code";
    public const SKU_KEY = "code";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }
    private function getFamilySKU(string $original_sku): string
    {
        switch (strlen($original_sku)) {
            case 8:
            case 9:
                return substr($original_sku, 0, -2);
            default:
                return $original_sku;
        }
    }
    private function isMTO(SimpleXMLElement $product): bool { return (strlen($product->{self::SKU_KEY}) < 8); }
    private function getPaddedForSorting(string $external_id): string { return Str::padLeft($external_id, 12, " "); }
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
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $ids->count();
        $imported_ids = [];

        foreach ($ids as [$sku, $external_id]) {
            $imported_ids[] = $external_id;

            if ($this->sync->current_module_data["current_external_id"] != null && $this->getPaddedForSorting($this->sync->current_module_data["current_external_id"]) > $this->getPaddedForSorting($external_id)) {
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

            if ($this->canProcessModule("marking")) {
                $this->prepareAndSaveMarkingData(compact("sku", "products", "markings"));
            }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($imported_ids);
                $imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($imported_ids);

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

        $products = ($product || $marking) ? $this->getProductInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        $markings = ($marking) ? $this->getMarkingInfo() : collect();

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                (string) $p->{self::SKU_KEY},
                (string) $p->{self::PRIMARY_KEY},
            ]);

        return compact(
            "ids",
            "products",
            "stocks",
            "markings",
        );
    }

    private function getProductInfo(): Collection
    {
        $products = Http::accept("application/xml")
            ->withToken(env("JAGUAR_API_TOKEN"), "Token")
            ->get(self::URL . "stock_items", [])
            ->throwUnlessStatus(200)
            ->body();
        $products = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($products)));

        // made to order products
        $mto_products = Http::accept("application/xml")
            ->withToken(env("JAGUAR_API_TOKEN"), "Token")
            ->get(self::URL . "made_to_order_items", [])
            ->throwUnlessStatus(200)
            ->body();
        $mto_products = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($mto_products)));

        $data = $products->merge($mto_products)
            ->sort(fn ($a, $b) => $this->getPaddedForSorting($a->{self::PRIMARY_KEY}) <=> $this->getPaddedForSorting($b->{self::PRIMARY_KEY}));

        return $data;
    }

    private function getStockInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->withToken(env("JAGUAR_API_TOKEN"), "Token")
            ->get(self::URL . "stock_count", [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($data)))
            ->sort(fn ($a, $b) => $this->getPaddedForSorting($a->{self::PRIMARY_KEY}) <=> $this->getPaddedForSorting($b->{self::PRIMARY_KEY}));

        return $data;
    }

    private function getMarkingInfo(): Collection
    {
        $markings = Http::accept("application/xml")
            ->withToken(env("JAGUAR_API_TOKEN"), "Token")
            ->get(self::URL . "marking", [])
            ->throwUnlessStatus(200)
            ->body();
        $markings = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($markings)));

        $setup_costs = Http::accept("application/xml")
            ->withToken(env("JAGUAR_API_TOKEN"), "Token")
            ->get(self::URL . "setup", [])
            ->throwUnlessStatus(200)
            ->body();
        $setup_costs = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($setup_costs)));

        $data = $markings->merge($setup_costs);

        return $data;
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

        $product = $products->firstWhere(fn($p) => $p->{self::SKU_KEY} == $sku);

        // MTOs have aggregated colors in one item
        $color_names = explode(", ", ((string) $product->colors) ?: ((string) $product->color_name));

        // normal products have images somehow split between one variant (treated as main) and the rest
        if ($this->isMTO($product)) {
            $images = collect($product->xpath("images/list-item"))
                ->map(fn($i) => (string) $i)
                ->toArray();
        } else {
            $family = $products->filter(fn ($p) => Str::startsWith($p->{self::SKU_KEY}, $this->getFamilySKU($sku)));
            $family_images = collect(
                $family->firstWhere(fn ($p) => collect($p->xpath("images/list-item"))->count() > 0)
                    ?->xpath("images/list-item")
                    ?? []
            )
                ->map(fn($i) => (string) $i)
                ->toArray();
            $images = [[(string) $product->color_image], $family_images];
        }

        foreach ($color_names as $i => $color_name) {
            $description = collect([
                "Rozmiar" => (string) $product->size,
                // "Waga" => (string) $product->weight, // does not exist in API
                "Materiał" => (string) $product->materials,
                "Minimalne zamówienie" => (string) $product->minimal_order,
            ])
                ->map(fn ($v, $k) => "<b>$k:</b> $v")
                ->join("<br>");

            $description .= Str::of(
                collect($product->xpath("features/list-item"))
                    ->map(fn ($i) => "<li>" . (string) $i . "</li>")
                    ->join("")
            )
                ->wrap("<ul>", "</ul>")
                ->toString();

            $ret[] = $this->saveProduct(
                $this->isMTO($product) ? $sku . "-$i" : $sku,
                (string) $product->{self::PRIMARY_KEY},
                str_replace($sku, $this->getFamilySKU($sku), (string) $product->name),
                $description,
                $this->getPrefixedId($this->getFamilySKU($sku)),
                as_number((string) $product->price_pln),
                $images,
                $images,
                $this->getPrefix(),
                $this->processTabs($product),
                collect($product->xpath("categories/list-item"))
                    ->map(fn($i) => (string) $i)
                    ->first(),
                $color_name,
                source: self::SUPPLIER_NAME,
            );
        }

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

        $stock = $stocks->firstWhere(self::SKU_KEY, $sku);

        return $this->saveStock(
            $this->getPrefixedId($sku),
            (int) $stock->count ?? 0,
            null,
            null,
        );
    }

    /**
     * @param array $data sku, products, markings
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
            "markings" => $markings,
        ] = $data;

        $product = $products->firstWhere(fn($p) => $p->{self::SKU_KEY} == $sku);
        // MTOs have non-obvious codes, I have to pull data from our own db to find them
        $products_to_add_markings = $this->isMTO($product)
            ? Product::where("product_family_id", $this->getPrefixedId($this->getFamilySKU($sku)))->get()->pluck("id")
            : [$this->getPrefixedId($sku)];

        foreach ($product->marking->children() ?? [] as $marking) {
            $marking = (string) $marking;
            $technique = $markings->firstWhere(fn ($m) => (string) $m->code == $marking);
            if (!$technique) continue;

            $setup = collect($technique->xpath("setup/list-item"))
                ->map(fn ($i) => as_number((string) $markings->firstWhere(fn ($m) => (string) $m->code == (string) $i)?->price_pln))
                ->filter()
                ->sum();

            foreach ($products_to_add_markings as $product_id) {
                $ret[] = $this->saveMarking(
                    $product_id,
                    "", // no positions available
                    (string) $technique->name,
                    null,
                    collect($technique->xpath("images/list-item"))
                        ->map(fn($i) => (string) $i)
                        ->toArray(),
                    null,
                    [
                        "1" => [
                            "price" => as_number((string) $technique->price_pln),
                            "allow_discount_on_setup" => true,
                        ],
                    ],
                    $setup,
                );
            }

        }

        $this->deleteCachedUnsyncedMarkings();

        return $ret;
    }

    private function processTabs(SimpleXMLElement $product) {
        //* product's sketch file is stored (supposedly) in the same directory as images
        // the directory name is not obvious and has to be extracted from image
        $sketch_url = null;
        preg_match('/products\/(\d+)/', (string) collect($product->xpath("images/list-item"))->first(), $directory_id);
        if (isset($directory_id[1])) {
            $sketch_url = implode("", [
                "https://jaguargift.com/media/products_files/",
                $directory_id[1],
                "/www_sketch_",
                $this->getFamilySKU((string) $product->{self::SKU_KEY}),
                "_doc.pdf",
            ]);
        }

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
                "cells" => [["type" => "tiles", "content" => array_filter([
                    "Zobacz miejsce znakowania" => $sketch_url,
                ])]],
            ],
        ]);
    }
    #endregion
}
