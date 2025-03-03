<?php

namespace App\DataIntegrators;

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
    public function getPrefix(): string { return "EA"; }
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
        $this->sync->addLog("pending", 1, "Synchronization started");

        $counter = 0;
        $total = 0;

        [
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

        $total = $products->count();
        $imported_ids = [];

        foreach ($products as $product) {
            $imported_ids[] = $this->getPrefixedId($product->baseinfo->{self::SKU_KEY});

            if ($this->sync->current_external_id != null && $this->sync->current_external_id > intval($product->baseinfo->{self::PRIMARY_KEY})) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$product->baseinfo->{self::PRIMARY_KEY}, $product->baseinfo->{self::PRIMARY_KEY});

            if ($this->sync->product_import_enabled) {
                $this->prepareAndSaveProductData(compact("product", "prices"));
            }

            if ($this->sync->stock_import_enabled) {
                $this->prepareAndSaveStockData(compact("product", "stocks"));
            }

            if ($this->sync->marking_import_enabled) {
                $this->prepareAndSaveMarkingData(compact("product", "markings"));
            }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->sync->product_import_enabled) $this->deleteUnsyncedProducts($imported_ids);
                $started_at = now();
            }
        }

        $this->reportSynchCount($counter, $total);
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
        $res = Http::acceptJson()
            ->get(self::URL . "json/stocks.json", [])
            ->throwUnlessStatus(200)
            ->collect();

        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row));

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
            $prices->firstWhere("ID", $product->baseinfo->{self::PRIMARY_KEY})["Price"],
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

        $stock = $stocks->firstWhere(self::PRIMARY_KEY, $product->baseinfo->{self::PRIMARY_KEY});
        if ($stock) $this->saveStock(
            $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
            $stock["Quantity24h"] /* + $stock["Quantity37days"] */,
            $stock["Quantity37days"],
            Carbon::today()->addDays(3)
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

        foreach ($product->markgroups?->children() ?? [] as $technique) {
            $marking = $markings->firstWhere("ID", $technique->id->__toString());
            if (!$marking) continue;

            $this->saveMarking(
                $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
                "", // no positions available
                $technique->name?->__toString(),
                $this->sanitizePrintSize($technique->marking_size?->__toString()),
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
