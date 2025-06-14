<?php

namespace App\DataIntegrators;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class MacmaHandler extends ApiHandler
{
    #region constants
    private const URL = "http://macma.pl/data/webapi/pl/xml/";
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

            if ($this->sync->current_external_id != null && $this->sync->current_external_id > $external_id) {
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

        [$products, $prices] = ($product || $marking) ? $this->getProductInfo() : [collect(), collect()];
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        $markings = ($marking) ? $this->getMarkingInfo() : collect();

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                (string) $p->baseinfo->{self::SKU_KEY} ?: (string) $p->{self::SKU_KEY},
                (string) $p->baseinfo->{self::PRIMARY_KEY} ?: (string) $p->{self::PRIMARY_KEY},
            ]);

        return compact(
            "ids",
            "products",
            "prices",
            "stocks",
            "markings",
        );
    }

    private function getStockInfo(): Collection
    {
        $stocks = Http::accept("text/xml")
            ->get(self::URL . "stocks.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $stocks = new SimpleXMLElement($stocks);
        $stocks = collect($this->mapXml(fn($p) => $p, $stocks))
            ->sort(fn ($a, $b) => (string) $a->{self::PRIMARY_KEY} <=> (string) $b->{self::PRIMARY_KEY});

        return $stocks;
    }

    private function getProductInfo(): array
    {
        $products = Http::accept("text/xml")
            ->get(self::URL . "offer.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $products = new SimpleXMLElement($products);
        $products = collect($this->mapXml(fn($p) => $p, $products))
            ->sort(fn ($a, $b) => (string) $a->baseinfo->{self::SKU_KEY} <=> (string) $b->baseinfo->{self::SKU_KEY});

        $prices = Http::accept("text/xml")
            ->get(self::URL . "prices.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $prices = new SimpleXMLElement($prices);
        $prices = collect($this->mapXml(fn($p) => $p, $prices));

        return [$products, $prices];
    }

    private function getMarkingInfo(): Collection
    {
        $markings = Http::accept("text/xml")
            ->get(self::URL . "markgroups.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $markings = new SimpleXMLElement($markings);
        $markings = collect($this->mapXml(fn($p) => $p, $markings));

        return $markings;
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products, prices
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "sku" => $sku,
            "products" => $products,
            "prices" => $prices,
        ] = $data;

        $product = $products->firstWhere(fn ($p) => (string) $p->baseinfo->{self::SKU_KEY} == $sku);

        $this->saveProduct(
            $product->baseinfo->{self::SKU_KEY},
            $product->baseinfo->{self::SKU_KEY},
            $product->baseinfo->name,
            $product->baseinfo->intro,
            $this->getPrefixedId($product->baseinfo->code_short),
            as_number((string) $prices->firstWhere(fn ($pr) => (string) $pr->{self::SKU_KEY} == (string) $product->baseinfo->{self::SKU_KEY})->price),
            collect($this->mapXml(fn($i) => $i?->__toString(), $product->images))->sort()->toArray(),
            collect($this->mapXml(fn($i) => $i?->__toString(), $product->images))->sort()->map(fn($img) => Str::replaceFirst('large-', 'small-', $img))->toArray(),
            $this->getPrefix(),
            $this->processTabs($product),
            collect($this->mapXml(
                fn ($cat) =>
                    $cat->name
                    . ($cat->subcategory ? " > ".$cat->subcategory->name : ""),
                $product->categories
            ))
                ->flatten()
                ->first(),
            $product->color->name,
            source: self::SUPPLIER_NAME,
            enable_discount: !$product->price_without_discount,
        );
    }

    /**
     * @param array $data sku, stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "sku" => $sku,
            "stocks" => $stocks,
        ] = $data;

        $stock = $stocks->firstWhere(fn ($pr) => (string) $pr->{self::SKU_KEY} == $sku);

        if ($stock) $this->saveStock(
            $this->getPrefixedId($sku),
            (int) $stock->quantity_24h,
            (int) $stock->quantity_37days,
            ((string) $stock->delivery_date) ? Carbon::parse((string) $stock->delivery_date) : null
        );
        else $this->saveStock($this->getPrefixedId($sku), 0);
    }

    /**
     * @param array $data sku, products, markings
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        [
            "sku" => $sku,
            "products" => $products,
            "markings" => $markings,
        ] = $data;

        $product = $products->firstWhere(fn ($p) => (string) $p->baseinfo->{self::SKU_KEY} == $sku);

        foreach ($product->markgroups->children() ?? [] as $technique) {
            $marking = $markings->firstWhere(fn ($m) => (int) $m->baseinfo->id == (int) $technique->id);
            if (!$marking) continue;

            $this->saveMarking(
                $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
                "", // no positions available
                $technique->name?->__toString(),
                $technique->marking_size?->__toString(),
                null,
                $marking["ColorsMax"] > 1
                    ? collect(range(1, (int) $marking->colors_max))
                        ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                            "mod" => "*$i",
                            "include_setup" => true,
                        ]])
                        ->toArray()
                    : null,
                collect(json_decode(json_encode($marking->prices->children()), true))
                    ->filter(fn ($p, $label) => Str::startsWith($label, "price_from"))
                    ->mapWithKeys(fn ($p, $label) => [$label => [
                        "price" => as_number($p) + as_number((string) $marking->price->pakowanie),
                    ]])
                    ->merge( // flat price defined for every quantity because packing price still has to count
                        collect(range(1, (int) $marking->price->ryczalt_quantity))
                            ->mapWithKeys(fn ($i) => ["price_from$i" => [
                                "price" => as_number((string) $marking->price->ryczalt_price) + as_number((string) $marking->price->pakowanie) * $i,
                                "flat" => true,
                            ]])
                    )
                    ->mapWithKeys(fn ($p, $label) => [Str::afterLast($label, "price_from") => $p])
                    ->sortBy(fn ($p, $label) => intval($label))
                    ->toArray(),
                as_number((string) $marking->price->przygotowanie),
            );
        }

        $this->deleteCachedUnsyncedMarkings();
    }

    private function processTabs(SimpleXMLElement $product) {
        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification = [
            "Rozmiar produktu" => $product->attributes->size?->__toString(),
            "Materiał" => implode(", ", $this->mapXml(fn ($m) => $m->name?->__toString(), $product->materials->material)),
            "Kraj pochodzenia" => $product->origincountry->name?->__toString(),
            "Marka" => $product->brand->name?->__toString(),
            "Waga" => $product->attributes->weight?->__toString(),
            "Kolor" => $product->color->name?->__toString(),
        ];

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
        $packaging = [
            "Opakowanie" => $product->packages->package?->name?->__toString(),
            "Małe opakowanie (szt.)" => $product->attributes->pack_small?->__toString(),
            "Duże opakowanie (szt.)" => $product->attributes->pack_large?->__toString(),
        ];

        //! markings
        $markings["Grupy i rozmiary znakowania"] = implode("\n", $this->mapXml(fn ($m) => $m->name?->__toString(), $product->markgroups));

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
    #endregion
}
