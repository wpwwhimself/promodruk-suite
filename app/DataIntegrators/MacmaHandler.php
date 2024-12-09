<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        [
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $this->downloadData(
            $sync->product_import_enabled,
            $sync->stock_import_enabled,
            $sync->marking_import_enabled
        );

        try
        {
            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids[] = $this->getPrefixedId($product->baseinfo->{self::SKU_KEY});

                if ($sync->current_external_id != null && $sync->current_external_id > intval($product->baseinfo->{self::SKU_KEY})) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => (string) $product->baseinfo->{self::SKU_KEY}]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product->baseinfo->{self::SKU_KEY});

                if ($sync->product_import_enabled) {
                    $this->prepareAndSaveProductData(compact("product", "prices"));
                }

                if ($sync->stock_import_enabled) {
                    $this->prepareAndSaveStockData(compact("product", "stocks"));
                }

                if ($sync->marking_import_enabled) {
                    $this->prepareAndSaveMarkingData(compact("product", "markings"));
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
        [$products, $prices] = $this->getProductInfo();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        $markings = ($marking) ? $this->getMarkingInfo() : collect();

        return compact(
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
        $stocks = collect($this->mapXml(fn($p) => $p, $stocks));

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
     * @param array $data product, prices
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "product" => $product,
            "prices" => $prices,
        ] = $data;

        $this->saveProduct(
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
     * @param array $data product, stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "product" => $product,
            "stocks" => $stocks,
        ] = $data;

        $stock = $stocks->firstWhere(fn ($pr) => (string) $pr->{self::SKU_KEY} == (string) $product->baseinfo->{self::SKU_KEY});
        if ($stock) $this->saveStock(
            $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
            (int) $stock->quantity_24h,
            (int) $stock->quantity_37days,
            ((string) $stock->delivery_date) ? Carbon::parse((string) $stock->delivery_date) : null
        );
        else $this->saveStock($this->getPrefixedId($product->baseinfo->{self::SKU_KEY}), 0);
    }

    /**
     * @param array $data product, markings
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        [
            "product" => $product,
            "markings" => $markings,
        ] = $data;

        foreach ($product->markgroups->children() ?? [] as $technique) {
            $marking = $markings->firstWhere(fn ($m) => (int) $m->baseinfo->id == (int) $technique->id);
            if (!$marking) continue;

            $this->saveMarking(
                $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
                "", // no positions available
                $technique->name?->__toString(),
                $this->sanitizePrintSize($technique->marking_size?->__toString()),
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
