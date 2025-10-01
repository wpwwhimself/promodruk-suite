<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class EasygiftsHandler extends ApiHandler
{
    #region constants
    private const URL = "https://www.easygifts.com.pl/data/webapi/pl/";
    private const SUPPLIER_NAME = "Easygifts";
    public function getPrefix(): array { return ["EA", "ZS"]; }
    private const PRIMARY_KEY = "id";
    public const SKU_KEY = "code_full";
    public function getPrefixedId(string $original_sku): string { return (Str::startsWith($original_sku, $this->getPrefix())) ? $original_sku : $this->getPrefix()[0] . $original_sku; }
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
        $res = Http::accept("text/xml")
            ->get(self::URL . "xml/stocks.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $res = new SimpleXMLElement($res);
        $res = collect($this->mapXml(fn($p) => $p, $res))
            ->sort(fn ($a, $b) => intval($a->{self::PRIMARY_KEY}) <=> intval($b->{self::PRIMARY_KEY}));

        return $res;
    }

    private function getProductInfo(): array
    {
        // prices
        $prices = Http::acceptJson()
            ->get(self::URL . "json/prices.json", [])
            ->throwUnlessStatus(200)
            ->collect();
        $header = $prices[0];
        $prices = $prices->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        // products
        $res = Http::accept("text/xml")
            ->get(self::URL . "xml/offer.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $res = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $res); // remove conflicting characters
        $res = new SimpleXMLElement($res);
        $res = collect($this->mapXml(fn($p) => $p, $res))
            ->sort(fn ($a, $b) => intval($a->baseinfo->{self::PRIMARY_KEY}) <=> intval($b->baseinfo->{self::PRIMARY_KEY}));

        return [$res, $prices];
    }

    private function getMarkingInfo(): Collection
    {
        $res = Http::acceptJson()
            ->get(self::URL . "json/markgroups.json", [])
            ->throwUnlessStatus(200)
            ->collect();

        $header = $res[0];
        $price_headers = [];
        foreach ($header as $i => $h) {
            if (is_array($h)) {
                $price_headers = $h;
                $header[$i] = "Price";
            }
        }
        $res = $res->skip(1)
            ->map(fn($row) => array_combine(
                $header,
                array_map(
                    fn ($cell) => is_array($cell) ? array_combine($price_headers, $cell) : $cell,
                    $row
                )
            ));

        return $res;
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products, prices
     */
    public function prepareAndSaveProductData(array $data): Product
    {
        [
            "sku" => $sku,
            "products" => $products,
            "prices" => $prices,
        ] = $data;

        $product = $products->firstWhere(fn ($p) => $p->baseinfo->{self::SKU_KEY} == $sku);

        return $this->saveProduct(
            $product->baseinfo->{self::SKU_KEY},
            $product->baseinfo->{self::PRIMARY_KEY},
            $product->baseinfo->name,
            $product->baseinfo->intro,
            $this->getPrefixedId($product->baseinfo->code_short),
            $prices->firstWhere("ID", $product->baseinfo->{self::PRIMARY_KEY})["Price"],
            collect($this->mapXml(fn($i) => $i?->__toString(), $product->images))->sort()->toArray(),
            collect($this->mapXml(fn($i) => $i?->__toString(), $product->images))->sort()->map(fn($img) => Str::replaceFirst('large-', 'small-', $img))->toArray(),
            Str::startsWith($product->baseinfo->{self::SKU_KEY}, $this->getPrefix())
                ? Str::substr($product->baseinfo->{self::SKU_KEY}, 0, 2)
                : $this->getPrefix()[0],
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
        );
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

        $stock = $stocks->firstWhere(fn ($s) => $s->{self::SKU_KEY} == $sku);

        if ($stock) return $this->saveStock(
            $this->getPrefixedId($sku),
            (int) $stock->quantity_24h /* + (int) $stock->quantity_37days */,
            (int) $stock->quantity_37days,
            Carbon::today()->addDays(3)
        );
        else return $this->saveStock($this->getPrefixedId($sku), 0);
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

        $product = $products->firstWhere(fn ($p) => $p->baseinfo->{self::SKU_KEY} == $sku);

        foreach ($product->markgroups?->children() ?? [] as $technique) {
            $marking = $markings->firstWhere("ID", $technique->id->__toString());
            if (!$marking) continue;

            $ret[] = $this->saveMarking(
                $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
                "", // no positions available
                $technique->name?->__toString(),
                $technique->marking_size?->__toString(),
                null,
                $marking["ColorsMax"] > 1
                    ? collect(range(1, $marking["ColorsMax"]))
                        ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                            "mod" => "*$i",
                            "include_setup" => true,
                        ]])
                        ->toArray()
                    : null,
                collect($marking["Price"])
                    ->filter(fn ($p, $label) => Str::startsWith($label, "Price From "))
                    ->mapWithKeys(fn ($p, $label) => [$label => [
                        "price" => as_number($p) + as_number($marking["Price"]["Pakowanie"]),
                    ]])
                    ->merge( // flat price defined for every quantity because packing price still has to count
                        collect(range(1, $marking["Price"]["Ryczalt quantity"]))
                            ->mapWithKeys(fn ($i) => ["Price From $i" => [
                                "price" => as_number($marking["Price"]["Ryczalt price"]) + as_number($marking["Price"]["Pakowanie"]) * $i,
                                "flat" => true,
                            ]])
                    )
                    ->mapWithKeys(fn ($p, $label) => [Str::afterLast($label, "Price From ") => $p])
                    ->sortBy(fn ($p, $label) => intval($label))
                    ->toArray(),
                as_number($marking["Price"]["Przygotowanie"])
            );
        }

        $this->deleteCachedUnsyncedMarkings();

        return $ret;
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
