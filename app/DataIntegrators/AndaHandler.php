<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                $imported_ids[] = $product[self::SKU_KEY];

                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["sku" => $product[self::SKU_KEY]]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product[self::PRIMARY_KEY]);

                if ($sync->product_import_enabled) {
                    $this->prepareAndSaveProductData(compact("product", "prices", "labelings"));
                }

                if ($sync->stock_import_enabled) {
                    $this->prepareAndSaveStockData(compact("product", "stocks"));
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
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY], "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        $prices = ($product) ? $this->getPriceInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        $labelings = ($product || $marking) ? $this->getLabelingInfo() : collect();

        return compact(
            "products",
            "prices",
            "stocks",
            "labelings",
        );
    }

    private function getStockInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "inventories/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect(
            json_decode(
                json_encode(
                    simplexml_load_string(
                        $data,
                        "SImpleXMLElement",
                        LIBXML_NOCDATA
                    )
                ),
                true
            )["record"]
        )
            ->groupBy(self::SKU_KEY);

        return $data;
    }

    private function getProductInfo(): Collection
    {
        $data = Http::accept("text/csv")
            ->get(self::URL . "products-csv/pl/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect(explode("\n", $data))
            ->filter(fn($row) => $row != "")
            ->map(fn($row) => collect(str_getcsv($row))
                ->map(fn($cell) => Str::contains($cell, "#")
                    ? explode("#", $cell)
                    : $cell
                )
                ->toArray()
            );

        $header = array_merge($data[0], ["createdAt"]);
        $data = $data->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        return $data;
    }

    private function getPriceInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "prices/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect(
            json_decode(
                json_encode(
                    simplexml_load_string(
                        $data,
                        "SImpleXMLElement",
                        LIBXML_NOCDATA
                    )
                ),
                true
            )["price"]
        );

        return $data;
    }

    private function getLabelingInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "labeling/pl/" . env("ANDA_API_KEY"), [])
            ->throwUnlessStatus(200)
            ->body();
        $data = collect(
            json_decode(
                json_encode(
                    simplexml_load_string(
                        $data,
                        "SImpleXMLElement",
                        LIBXML_NOCDATA
                    )
                ),
                true
            )["labeling"]
        );

        return $data;
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
            $product[self::SKU_KEY],
            $product["name"],
            $product["descriptions"],
            $product["rootItemNumber"],
            as_number($prices->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY])["amount"] ?? 0),
            collect($product["images"])->toArray(),
            collect($product["images"])->toArray(),
            $this->getPrefix(),
            $this->processTabs($product, $labelings->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY])),
            collect($product["categories"])
                ->map(fn($cat) => $this->processArrayLike($cat))
                ->sortBy("level")
                ->map(fn($lvl) => $lvl["name"] ?? "")
                ->join(" > "),
            $product["secondaryColor"]
                ? implode("/", [$product["primaryColor"], $product["secondaryColor"]])
                : $product["primaryColor"],
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

        $stock = $stocks[$product[self::SKU_KEY]] ?? null;

        if ($stock) {
            $stock = $stock->sortBy("arrivalDate");

            $this->saveStock(
                $product[self::SKU_KEY],
                $stock->firstWhere("type", "central_stock")["amount"] ?? 0,
                $stock->firstWhere("type", "incoming_to_central_stock")["amount"] ?? null,
                Carbon::parse($stock->firstWhere("type", "incoming_to_central_stock")["arrivalDate"] ?? null) ?? null
            );
        }
        else $this->saveStock($product[self::SKU_KEY], 0);
    }

    /**
     * @param array $data ???
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        // unavailable yet
    }

    private function processArrayLike(string $data): array | null
    {
        if ($data == "") return null;

        // find keys
        preg_match_all('/(\w+):/', $data, $matches, PREG_OFFSET_CAPTURE);

        for ($i = 0; $i < count($matches[0]); $i++) {
            // Extract key and position
            $key = $matches[1][$i][0];
            $startPos = $matches[0][$i][1];

            // Determine where the value starts
            $valueStartPos = $startPos + strlen($key) + 1;

            // Find the end position of the value, which is either the next key or the end of the string
            $endPos = ($i + 1 < count($matches[0]))
                ? $matches[0][$i + 1][1] - 1
                : strlen($data);

            $value = trim(substr($data, $valueStartPos, $endPos - $valueStartPos));
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));

            $result[$camelKey] = $value;
        }

        return $result;
    }

    private function processTabs(array $product, ?array $labeling) {
        //! specification
        $specification = collect([
            "countryOfOrigin" => "Kraj pochodzenia",
            "individualProductWeightGram" => "Waga produktu [g]",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $product[$item] ?? null])
            ->merge(($product["specification"] == "")
                ? null
                : collect($product["specification"])
                    ->map(fn($cat) => $this->processArrayLike($cat))
                    ->mapWithKeys(fn($spec) => [$spec["name"] => Str::unwrap($spec["values"], "[", "]")])
            )
            ->toArray();

        //! packaging
        $packaging_data = empty($product["packageDatas"]) ? null : collect($product["packageDatas"])
            ->map(fn($det) => $this->processArrayLike($det))
            ->mapWithKeys(fn($det) => [$det["code"] => $det])
            ->flatMap(fn($det, $type) => collect($det)
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
            : collect(isset($labeling["positions"]["position"]["serial"])
                ? $labeling["positions"]
                : $labeling["positions"]["position"]
            )
            ->flatMap(function ($pos) {
                $arr = collect([[
                    "heading" => "$pos[serial]. $pos[posName]",
                    "type" => "tiles",
                    "content" => $pos["posImage"] ? ["pozycja" => $pos["posImage"]] : null,
                ]]);
                collect(isset($pos["technologies"]["technology"]["Code"])
                    ? $pos["technologies"]
                    : $pos["technologies"]["technology"]
                )
                    ->each(fn($tech) => $arr = $arr->push([
                        "type" => "table",
                        "content" => [
                            "Technika" => "$tech[Name] ($tech[Code])",
                            "Maksymalna liczba kolorów" => $tech["maxColor"],
                            "Maksymalna szerokość [mm]" => $tech["maxWmm"] ?: null,
                            "Maksymalna wysokość [mm]" => $tech["maxHmm"] ?: null,
                            "Maksymalna średnica [mm]" => $tech["maxDmm"] ?: null,
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
