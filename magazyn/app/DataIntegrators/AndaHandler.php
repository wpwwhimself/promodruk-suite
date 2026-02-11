<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

ini_set("memory_limit", "512M");

class AndaHandler extends ApiHandler
{
    #region constants
    private const URL = "https://xml.andapresent.com/export/";
    private const SUPPLIER_NAME = "Anda";
    public function getPrefix(): string { return "AP"; }
    private const PRIMARY_KEY = "itemNumber";
    public const SKU_KEY = "itemNumber";
    public function getPrefixedId(string $original_sku): string { return $original_sku; }

    private function getSortableId(string $sku): string
    {
        $parts = preg_split("/[\-_]/", $sku, 2);
        $parts[0] = Str::of($parts[0])->replace($this->getPrefix(), "");
        $parts[1] = (isset($parts[1]))
            ? Str::of($parts[1])
                ->substr(0, 2)
                ->replaceMatches("/\\D/", "")
                ->padLeft(2, "0")
            : "00";

        return $parts[0] . $parts[1];
    }
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
            "labelings" => $labelings,
            "labeling_prices" => $labeling_prices,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $ids->count();
        $imported_ids = [];

        foreach ($ids as [$sku, $external_id]) {
            $imported_ids[] = $this->getSortableId($external_id);

            if ($this->sync->current_module_data["current_external_id"] != null && $this->sync->current_module_data["current_external_id"] > $external_id) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("sku", "products", "prices", "labelings"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            if ($this->canProcessModule("marking")) {
                $this->prepareAndSaveMarkingData(compact("sku", "products", "labelings", "labeling_prices"));
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
        $prices = ($product) ? $this->getPriceInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        [$labelings, $labeling_prices] = ($product || $marking) ? $this->getLabelingInfo() : [collect(),collect()];

        $ids = ($products->count() > 0)
            ? $products->map(fn ($p) => [
                Str::of((string) $p->{self::SKU_KEY})->beforeLast("_"),
                (string) $p->{self::PRIMARY_KEY},
            ])
            : $stocks->map(fn ($p, $k) => [
                $k,
                $k,
            ]);
        $ids = $ids->unique();

        return compact(
            "ids",
            "products",
            "prices",
            "stocks",
            "labelings",
            "labeling_prices",
        );
    }

    private function getStockInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "inventories/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($data)))
            ->sortBy(fn($p) => (string) $p->{self::SKU_KEY})
            ->groupBy(fn($i) => $i->{self::SKU_KEY});

        return $data;
    }

    private function getProductInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "products/pl/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($data)))
            ->sortBy(fn($p) => $this->getSortableId($p->{self::PRIMARY_KEY}));

        return $data;
    }

    private function getPriceInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "prices/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($data)));

        return $data;
    }

    private function getLabelingInfo(): array
    {
        $labelings = Http::accept("application/xml")
            ->get(self::URL . "labeling/pl/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $labelings = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($labelings)));

        $prices_raw = Http::accept("application/xml")
            ->get(self::URL . "printingprices/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $prices_raw = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($prices_raw)))->last();
        $prices_raw = collect($this->mapXml(fn($p) => $p, $prices_raw)); // get prices from priceList

        // take all prices and pull only important data: technique, print size range, quantity range and price
        $prices = collect();
        foreach ($prices_raw as $price) {
            foreach ($price->ranges->range as $range) {
                $prices->push([
                    "TechnologyCode" => (string) $price->TechnologyCode,
                    "NumberOfColours" => is_numeric((string) $range->NumberOfColours) ? (int) $range->NumberOfColours : 1,
                    "SizeFrom" => (float) $range->SizeFrom,
                    "SizeTo" => (float) $range->SizeTo,
                    "QuantityFrom" => (int) $range->QuantityFrom,
                    "QuantityTo" => (int) $range->QuantityTo,
                    "UnitPrice" => (float) $range->UnitPrice,
                    "SetupCost" => (float) $range->SetupCost,
                ]);
            }
        };

        return [$labelings, $prices];
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products, prices, labelings
     */
    public function prepareAndSaveProductData(array $data): Product
    {
        [
            "sku" => $sku,
            "products" => $products,
            "prices" => $prices,
            "labelings" => $labelings,
        ] = $data;

        $product = $products->filter(fn($p) => Str::startsWith((string) $p->{self::SKU_KEY}, $sku));
        $sizes = null;
        if ($product->count() > 1) {
            $sizes = $product;
        }
        $product = $product->first();

        return $this->saveProduct(
            Str::beforeLast($product->{self::SKU_KEY}, "_"),
            $this->getSortableId($product->{self::PRIMARY_KEY}),
            $product->name,
            $product->descriptions,
            $product->rootItemNumber,
            as_number((string) $prices->firstWhere(fn($p) => (string) $p->{self::PRIMARY_KEY} == (string) $product->{self::PRIMARY_KEY})?->amount ?? 0),
            $this->mapXml(fn($i) => (string) $i, $product->images),
            $this->mapXml(fn($i) => (string) $i, $product->images),
            $this->getPrefix(),
            $this->processTabs($product, $labelings->firstWhere(fn($l) => (string) $l->{self::PRIMARY_KEY} == (string) $product->{self::PRIMARY_KEY})),
            collect($product->xpath("categories/category"))
                ->sortBy("level")
                ->map(fn($lvl) => (string) $lvl->name ?? "")
                ->join(" > "),
            !empty((string) $product->secondaryColor)
                ? implode("/", [$product->primaryColor, $product->secondaryColor])
                : $product->primaryColor,
            source: self::SUPPLIER_NAME,
            sizes: $sizes?->map(fn ($s) => [
                "size_name" => Str::of($s->{self::SKU_KEY})->afterLast("_"),
                "size_code" => Str::of($s->{self::SKU_KEY})->afterLast("_"),
                "full_sku" => $s->{self::SKU_KEY},
            ])->toArray(),
            manipulation_cost: 0, //todo is there manipulation cost?
            marked_as_new: array_search("New", $this->mapXml(fn($i) => (string) $i->name, $product->productTheme->flags)) !== false,
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

        $stock = $stocks[$sku] ?? null;

        if ($stock) {
            $stock = $stock->sortBy(fn($s) => $s->arrivalDate);

            return $this->saveStock(
                $sku,
                (int) $stock->firstWhere(fn($s) => (string) $s->type == "central_stock")?->amount ?? 0,
                (int) $stock->firstWhere(fn($s) => (string) $s->type == "incoming_to_central_stock")?->amount ?? null,
                Carbon::parse($stock->firstWhere(fn($s) => (string) $s->type == "incoming_to_central_stock")?->arrivalDate ?? null) ?? null
            );
        }
        else return $this->saveStock($sku, 0);
    }

    /**
     * @param array $data sku, products, labelings, labeling_prices
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
            "labelings" => $labelings,
            "labeling_prices" => $labeling_prices,
        ] = $data;

        $product = $products->firstWhere(fn($p) => $p->{self::SKU_KEY} == $sku);
        $labeling = $labelings->firstWhere(fn($l) => (string) $l->{self::PRIMARY_KEY} == (string) $product->{self::PRIMARY_KEY});
        if (!$labeling) return null;

        collect($this->mapXml(fn($p) => $p, $labeling->positions))->each(fn($position) =>
            collect($this->mapXml(fn($i) => $i, $position->technologies))->each(function($technique) use ($product, $position, $labeling_prices) {
                $print_area_mm2 = $technique->maxWmm * $technique->maxHmm;
                $prices = $labeling_prices->filter(fn($p) =>
                    Str::startsWith($p["TechnologyCode"], (string) $technique->Code)
                    && (
                        $p["SizeFrom"] <= $print_area_mm2/100 && $p["SizeTo"] >= $print_area_mm2/100
                        || $p["SizeFrom"] == 0 && $p["SizeTo"] == 0
                    )
                );

                $max_color_count = is_numeric((string) $technique->maxColor) ? (int) $technique->maxColor : 1;
                for ($color_count = 1; $color_count <= $max_color_count; $color_count++) {
                    $color_count_prices = $prices->filter(fn($p) => $p["NumberOfColours"] == $color_count);
                    $ret[] = $this->saveMarking(
                        $this->getPrefixedId($product->{self::SKU_KEY}),
                        $position->posName,
                        $technique->Name
                        . (
                            !empty((int) $technique->maxColor)
                            ? " ($color_count kolor" . ($color_count >= 5 ? "ów" : ($color_count == 1 ? "" : "y")) . ")"
                            : ""
                        ),
                        $technique->maxWmm."x".$technique->maxHmm." mm",
                        empty((string) $position->posImage) ? null : [(string) $position->posImage],
                        null, // multiple color pricing done as separate products, due to the way prices work
                        $color_count_prices
                            ->mapWithKeys(fn($p) => [$p["QuantityFrom"] => [
                                "price" => $p["UnitPrice"],
                            ]])
                            ->toArray(),
                        $color_count_prices->first()["SetupCost"] ?? null,
                    );
                }
            })
        );

        $this->deleteCachedUnsyncedMarkings();

        return $ret;
    }

    private function processTabs(SimpleXMLElement $product, ?SimpleXMLElement $labeling) {
        //! specification
        $specification = collect([
            "countryOfOrigin" => "Kraj pochodzenia",
            "individualProductWeightGram" => "Waga produktu [g]",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => ((string) $product->{$item}) ?? null])
            ->merge(($product->specification == "")
                ? null
                : collect($product->specification)
                    ->mapWithKeys(fn($spec) => [((string) $spec->name) => Str::unwrap((string) $spec->values, "[", "]")])
            )
            ->toArray();

        //! packaging
        $packaging_data = collect($this->mapXml(fn($i) => $i, $product->packageDatas))
            ->mapWithKeys(fn($det) => [((string) $det->code) => $det])
            ->flatMap(fn($det, $type) => collect((array) $det)
                ->mapWithKeys(fn($val, $key) => ["$type.$key" => $val])
            )
            ->toArray();
        $packaging = empty($packaging_data) ? null : collect([
            "master carton.quantity" => "Ilość",
            "master carton.grossWeight" => "Waga brutto [kg]",
            "master carton.weight" => "Waga netto [kg]",
            "master carton.length;master carton.width;master carton.height" => "Wymiary kartonu [cm]",
            "master carton.cubage" => "Kubatura [m³]",
            "inner carton.quantity" => "Ilość w kartonie wewnętrznym",
        ])
            ->mapWithKeys(fn($label, $item) => [
                $label => collect(explode(";", $item))
                    ->map(fn($iitem) => $packaging_data[$iitem] ?? null)
                    ->join(" × ")
            ])
            ->toArray();

        //! markings
        $markings = !$labeling
            ? null
            : collect(is_array($labeling->positions->position)
                ? $labeling->positions->position
                : [$labeling->positions->position]
            )
            ->flatMap(function ($pos) {
                $arr = collect([[
                    "heading" => "$pos->serial. $pos->posName",
                    "type" => "tiles",
                    "content" => array_filter(["pozycja" => (string) $pos->posImage]),
                ]]);
                collect(is_array($pos->technologies->technology)
                    ? $pos->technologies->technology
                    : [$pos->technologies->technology]
                )
                    ->each(fn($tech) => $arr = $arr->push([
                        "type" => "table",
                        "content" => [
                            "Technika" => "$tech->Name ($tech->Code)",
                            "Maksymalna liczba kolorów" => (string) $tech->maxColor,
                            "Maksymalna szerokość [mm]" => ((string) $tech->maxWmm) ?: null,
                            "Maksymalna wysokość [mm]" => ((string) $tech->maxHmm) ?: null,
                            "Maksymalna średnica [mm]" => ((string) $tech->maxDmm) ?: null,
                        ]
                    ]));
                return $arr->toArray();
            });

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
            $packaging ? [
                "name" => "Opakowanie",
                "cells" => [["type" => "table", "content" => $packaging]],
            ] : null,
            $markings ? [
                "name" => "Znakowanie",
                "cells" => $markings,
            ] : null,
        ]);
    }
    #endregion
}
