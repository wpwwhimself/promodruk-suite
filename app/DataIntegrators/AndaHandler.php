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
    private const URL = "https://xml.andapresent.com/export/";
    private const SUPPLIER_NAME = "Anda";
    public function getPrefix(): string { return "AP"; }
    private const PRIMARY_KEY = "itemNumber";
    private const SKU_KEY = "itemNumber";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now(), "synch_status" => 0]);

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        $prices = $this->getPriceInfo();
        $labelings = $this->getLabelingInfo();
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo();

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["sku" => $product[self::SKU_KEY]]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY], "synch_status" => 1]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product["itemNumber"],
                        $product["name"],
                        $product["descriptions"],
                        $product["rootItemNumber"],
                        as_number($prices->firstWhere("itemNumber", $product["itemNumber"])["amount"] ?? 0),
                        collect($product["images"])->toArray(),
                        collect($product["images"])->toArray(),
                        $product["itemNumber"],
                        $this->processTabs($product, $labelings->firstWhere("itemNumber", $product["itemNumber"])),
                        collect($product["categories"])
                            ->map(fn($cat) => $this->processArrayLike($cat))
                            ->sortBy("level")
                            ->map(fn($lvl) => $lvl["name"] ?? "")
                            ->join(" > "),
                        $product["primaryColor"]
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks[$product["itemNumber"]] ?? null;
                    if ($stock) {
                        $stock = $stock->sortBy("arrivalDate");

                        $this->saveStock(
                            $product["itemNumber"],
                            $stock->firstWhere("type", "central_stock")["amount"] ?? 0,
                            $stock->firstWhere("type", "incoming_to_central_stock")["amount"] ?? null,
                            Carbon::parse($stock->firstWhere("type", "incoming_to_central_stock")["arrivalDate"] ?? null) ?? null
                        );
                    }
                    else $this->saveStock($product["itemNumber"], 0);
                }

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null, "synch_status" => 3]);
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY], "exception" => $e]);
            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["synch_status" => 2]);
        }
    }

    private function getStockInfo(): Collection
    {
        $data = Http::accept("application/xml")
            ->get(self::URL . "inventories/" . env("ANDA_API_KEY"), [])
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
            ->groupBy("itemNumber");

        return $data;
    }

    private function getProductInfo(): Collection
    {
        $data = Http::accept("text/csv")
            ->get(self::URL . "products-csv/pl/" . env("ANDA_API_KEY"), [])
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

    private function processArrayLike(string $data): array
    {
        if ($data == "") return null;
        $res = [];

        // find keys
        preg_match_all('/(\w+):/', $data, $matches, PREG_OFFSET_CAPTURE);

        $lastPos = null;
        for ($i = 0; $i < count($matches[0]); $i++) {
            // Extract key and position
            $key = $matches[1][$i][0];  // The actual key
            $startPos = $matches[0][$i][1];  // Position of the key in the string

            // Determine where the value starts
            $valueStartPos = $startPos + strlen($key) + 1; // after the key and colon

            // Find the end position of the value, which is either the next key or the end of the string
            if ($i + 1 < count($matches[0])) {
                $endPos = $matches[0][$i + 1][1] - 1; // Just before the next key
            } else {
                $endPos = strlen($data); // Last value goes to the end of the string
            }

            // Extract the value and trim any spaces
            $value = trim(substr($data, $valueStartPos, $endPos - $valueStartPos));

            // Convert the key to camelCase
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));

            // Add key-value pair to result
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
        $packaging_data = collect($product["packageDatas"])
            ->map(fn($det) => $this->processArrayLike($det))
            ->mapWithKeys(fn($det) => [$det["code"] => $det])
            ->flatMap(fn($det, $type) => collect($det)
                ->mapWithKeys(fn($val, $key) => ["$type.$key" => $val])
            )
            ->toArray();
        $packaging = collect([
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
                    "content" => ["pozycja" => $pos["posImage"]],
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
            [
                "name" => "Opakowanie",
                "cells" => [["type" => "table", "content" => $packaging ?? []]],
            ],
            $markings ? [
                "name" => "Znakowanie",
                "cells" => $markings,
            ] : null,
        ]);
    }

    public function test(string $itemNumber)
    {
        dd(
            $this->getProductInfo()->firstWhere(self::PRIMARY_KEY, $itemNumber),
            $this->getLabelingInfo()->firstWhere(self::PRIMARY_KEY, $itemNumber),
        );
    }
}
