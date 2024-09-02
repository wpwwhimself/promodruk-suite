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
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

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
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product[self::PRIMARY_KEY]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product["itemNumber"],
                        $product["name"],
                        $product["descriptions"],
                        $product["rootItemNumber"],
                        as_number($prices->firstWhere("itemNumber", $product["itemNumber"])["amount"]),
                        $product["images"],
                        $product["images"],
                        $product["itemNumber"],
                        $this->processTabs($product, $labelings->firstWhere("itemNumber", $product["itemNumber"])),
                        collect($product["categories"])
                            ->map(fn($cat) => json_decode("{".$cat."}"))
                            ->sortBy("level")
                            ->map(fn($lvl) => $lvl["name"])
                            ->join(" > "),
                        $product["primaryColor"]
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks[$product["itemNumber"]] ?? null;
                    if ($stock) {
                        $stock = $stock->orderBy("arrivalDate");

                        $this->saveStock(
                            $product["itemNumber"],
                            $stock->firstWhere("type", "central_stock")["amount"],
                            $stock->firstWhere("type", "incoming_to_central_stock")["amount"],
                            Carbon::parse($stock->firstWhere("type", "incoming_to_central_stock")["arrivalDate"])
                        );
                    }
                    else $this->saveStock($product["itemNumber"], 0);
                }

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null]);
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY]]);
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

    private function processTabs(array $product, ?array $labeling) {
        //! specification
        $specification = collect($product["specification"])
            ->map(fn($cat) => json_decode("{".$cat."}"))
            ->mapWithKeys(fn($spec) => [$spec["name"] => Str::unwrap($spec["values"], "[", "]")]);

        //! packaging
        // $packaging = ;

        //! markings
        $markings = collect($labeling)
            ?->get("positions.position")
            ->map(fn($pos) => [
                [
                    "heading" => "$pos[serial]. $pos[posName]",
                    "type" => "tiles",
                    "content" => ["pozycja" => $pos["posImage"]],
                ],
                [
                    "type" => "table",
                    "content" => collect(isset($pos["technologies"]["technology"]["Code"]) ? $pos["technologies"] : $pos["technologies"]["technology"])
                        ->map(fn($tech) => [
                            "Technika" => "$tech[name] ($tech[Code])",
                            "Maksymalna liczba kolorów" => $tech["maxColor"],
                            "Maksymalna szerokość [mm]" => $tech["maxWmm"] ?: null,
                            "Maksymalna wysokość [mm]" => $tech["maxHmm"] ?: null,
                            "Maksymalna średnica [mm]" => $tech["maxDmm"] ?: null,
                        ])
                        ->flatten(1)
                        ->toArray(),
                ],
            ])
            ->flatten(1);

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
                "name" => "Znakowanie",
                "cells" => [$markings],
            ],
            [
                "name" => "Opakowanie",
                "cells" => [["type" => "text", "content" => "🚧tbd"]],
            ],
        ]);
    }
}
