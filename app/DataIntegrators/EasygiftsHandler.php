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

class EasygiftsHandler extends ApiHandler
{
    private const URL = "https://www.easygifts.com.pl/data/webapi/pl/";
    private const SUPPLIER_NAME = "Easygifts";
    public function getPrefix(): string { return "EA"; }
    private const PRIMARY_KEY = "id";
    private const SKU_KEY = "code_full";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        [$products, $prices] = $this->getProductInfo();
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo();

        try
        {
            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids[] = $this->getPrefix() . $product["baseinfo"][self::SKU_KEY];

                if ($sync->current_external_id != null && $sync->current_external_id > $product["baseinfo"][self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product["baseinfo"][self::PRIMARY_KEY], "sku" => $product["baseinfo"][self::SKU_KEY]]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product["baseinfo"][self::PRIMARY_KEY]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $this->getPrefix() . $product["baseinfo"][self::SKU_KEY],
                        $product["baseinfo"]["name"],
                        $product["baseinfo"]["intro"],
                        $this->getPrefix() . $product["baseinfo"]["code_short"],
                        $prices->firstWhere("ID", $product["baseinfo"][self::PRIMARY_KEY])["Price"],
                        collect($product["images"])->sort()->toArray(),
                        collect($product["images"])->sort()->map(fn($img) => Str::replaceFirst('large-', 'small-', $img))->toArray(),
                        $product["baseinfo"][self::SKU_KEY],
                        $this->processTabs($product),
                        collect(array_map(
                            fn ($cat) =>
                                "$cat[name]"
                                . (isset($cat["subcategory"]) ? " > ".$cat["subcategory"]["name"] : ""),
                            $product["categories"]
                        ))
                            ->flatten()
                            ->first(),
                        $product["color"]["name"],
                        source: self::SUPPLIER_NAME,
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks->firstWhere(self::PRIMARY_KEY, $product["baseinfo"][self::PRIMARY_KEY]);
                    if ($stock) $this->saveStock(
                        $this->getPrefix() . $product["baseinfo"][self::SKU_KEY],
                        $stock["Quantity24h"] /* + $stock["Quantity37days"] */,
                        $stock["Quantity37days"],
                        Carbon::today()->addDays(3)
                    );
                    else $this->saveStock($this->getPrefix() . $product["baseinfo"][self::SKU_KEY], 0);
                }

                if ($sync->marking_import_enabled) {
                    // foreach ($positions as $position) {
                    //     foreach ($position["marking_option"] as $technique) {
                    //         $this->saveMarking(
                    //             $this->getPrefix() . $product["baseinfo"][self::SKU_KEY],
                    //             "$position[name_pl] ($position[code])",
                    //             $marking_labels[$technique["option_label"]] . " ($technique[option_code])",
                    //             $technique["option_info"],
                    //             [$technique["marking_area_img"]],
                    //             $technique["max_colors"] > 0
                    //                 ? collect()->range(1, $technique["max_colors"])
                    //                     ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                    //                         "mod" => "*$i",
                    //                     ]])
                    //                     ->toArray()
                    //                 : null,
                    //             collect($marking_prices->firstWhere("code", $technique["option_code"])["main_marking_price"])
                    //                 ->mapWithKeys(fn ($p) => [$p["from_qty"] => [
                    //                     "price" => $p["price_pln"],
                    //                 ]])
                    //                 ->toArray(),
                    //         );
                    //     }
                    // }
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
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product["baseinfo"][self::PRIMARY_KEY], "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
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
        $res = simplexml_load_string($res);
        $res = collect($res["product"])
            ->sort(fn ($a, $b) => $a["baseinfo"][self::PRIMARY_KEY] <=> $b["baseinfo"][self::PRIMARY_KEY]);

        return [$res, $prices];
    }

    private function processTabs(array $product) {
        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification = [
            "Rozmiar produktu" => $product["attributes"]["size"],
            "Materiał" => implode(", ", array_map(fn ($m) => $m["name"], $product["materials"]["material"])),
            "Kraj pochodzenia" => $product["origincountry"]["name"],
            "Marka" => $product["brand"]["name"],
            "Waga" => $product["attributes"]["weight"],
            "Kolor" => $product["color"]["name"],
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
            "Opakowanie" => $product["packages"]["package"]["name"],
            "Małe opakowanie (szt.)" => $product["attributes"]["pack_small"],
            "Duże opakowanie (szt.)" => $product["attributes"]["pack_large"],
        ];

        //! markings
        $markings["Grupy i rozmiary znakowania"] = implode("\n", array_map(fn ($m) => $m["name"], $product["markgroups"]));

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
}
