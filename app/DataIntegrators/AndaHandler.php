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
            "labelings" => $labelings,
            "labeling_prices" => $labeling_prices,
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
                $imported_ids[] = $product->{self::SKU_KEY};

                if ($sync->current_external_id != null && $sync->current_external_id > $product->{self::PRIMARY_KEY}) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["sku" => $product->{self::SKU_KEY}]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product->{self::PRIMARY_KEY});

                if ($sync->product_import_enabled) {
                    $this->prepareAndSaveProductData(compact("product", "prices", "labelings"));
                }

                if ($sync->stock_import_enabled) {
                    $this->prepareAndSaveStockData(compact("product", "stocks"));
                }

                if ($sync->marking_import_enabled) {
                    $this->prepareAndSaveMarkingData(compact("product", "labelings", "labeling_prices"));
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
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product->{self::PRIMARY_KEY}, "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        $products = $this->getProductInfo();
        $prices = ($product) ? $this->getPriceInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        [$labelings, $labeling_prices] = ($product || $marking) ? $this->getLabelingInfo() : collect();

        return compact(
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
            ->sortBy(fn($p) => (string) $p->{self::PRIMARY_KEY});

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

        $prices = Http::accept("application/xml")
            ->get(self::URL . "labeling/pl/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $prices = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($prices)));

        return [$labelings, $prices];
    }
    #endregion

    #region processing
    /**
     * @param array $data product, prices, labelings
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "product" => $product,
            "prices" => $prices,
            "labelings" => $labelings,
        ] = $data;

        $this->saveProduct(
            $product->{self::SKU_KEY},
            $product->name,
            $product->descriptions,
            $product->rootItemNumber,
            as_number((string) $prices->firstWhere(fn($p) => (string) $p->{self::PRIMARY_KEY} == (string) $product->{self::PRIMARY_KEY})?->amount ?? 0),
            $this->mapXml(fn($i) => (string) $i, $product->images),
            $this->mapXml(fn($i) => (string) $i, $product->images),
            $this->getPrefix(),
            $this->processTabs($product, $labelings->firstWhere(fn($l) => (string) $l->{self::PRIMARY_KEY} == (string) $product->{self::PRIMARY_KEY})),
            collect($product->categories)
                ->sortBy("level")
                ->map(fn($lvl) => $lvl["name"] ?? "")
                ->join(" > "),
            !empty((string) $product->secondaryColor)
                ? implode("/", [$product->primaryColor, $product->secondaryColor])
                : $product->primaryColor,
            source: self::SUPPLIER_NAME,
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

        $stock = $stocks[(string) $product->{self::SKU_KEY}] ?? null;

        if ($stock) {
            $stock = $stock->sortBy(fn($s) => $s->arrivalDate);

            $this->saveStock(
                $product->{self::SKU_KEY},
                (int) $stock->firstWhere(fn($s) => (string) $s->type == "central_stock")?->amount ?? 0,
                (int) $stock->firstWhere(fn($s) => (string) $s->type == "incoming_to_central_stock")?->amount ?? null,
                Carbon::parse($stock->firstWhere(fn($s) => (string) $s->type == "incoming_to_central_stock")?->arrivalDate ?? null) ?? null
            );
        }
        else $this->saveStock($product->{self::SKU_KEY}, 0);
    }

    /**
     * @param array $data product, labelings, labeling_prices
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        // [
        //     "product" => $product,
        //     "labelings" => $labelings,
        //     "labeling_prices" => $labeling_prices,
        // ] = $data;
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
