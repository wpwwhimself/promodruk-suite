<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PARHandler extends ApiHandler
{
    private const URL = "https://www.par.com.pl/api/";
    private const SUPPLIER_NAME = "PAR";
    public function getPrefix(): string { return "R"; }
    private const PRIMARY_KEY = "id";
    private const SKU_KEY = "kod";

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo()->sortBy(self::PRIMARY_KEY);
        if ($sync->marking_import_enabled)
            $markings = $this->getMarkingInfo();

        try
        {
            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product[self::PRIMARY_KEY]) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => $product[self::PRIMARY_KEY], "sku" => $product[self::SKU_KEY]]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product[self::PRIMARY_KEY]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product[self::SKU_KEY],
                        $product["nazwa"],
                        $product["opis"],
                        Str::beforeLast($product[self::SKU_KEY], "."),
                        $product["cena_pln"],
                        collect($product["zdjecia"])->sortBy("zdjecie")->map(fn($i) => "https://www.par.com.pl". $i["zdjecie"])->toArray(),
                        collect($product["zdjecia"])
                            ->sortBy("zdjecie")
                            ->map(fn($i) => "https://www.par.com.pl". $i["zdjecie"])
                            ->map(fn($i) => str_replace("/full", "/pelne", $i))
                            ->map(fn($i) => str_replace(".jpg", ".png", $i))
                            ->toArray(),
                        $product[self::SKU_KEY],
                        $this->processTabs($product),
                        collect($product["kategorie"])->first()["name"],
                        $product["kolor_podstawowy"],
                        source: self::SUPPLIER_NAME,
                    );
                    $imported_ids[] = $product[self::SKU_KEY];
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY]);
                    if ($stock) $this->saveStock(
                        $product[self::SKU_KEY],
                        $stock["stan_magazynowy"],
                        $stock["ilosc_dostawy"],
                        isset($stock["data_dostawy"]) ? Carbon::parse($stock["data_dostawy"]) : null
                    );
                    else $this->saveStock($product[self::SKU_KEY], 0);
                }

                if ($sync->marking_import_enabled) {
                    foreach ($product["techniki_zdobienia"] as $technique) {
                        $marking = $markings->firstWhere("id", $technique["technic_id"]);
                        $this->saveMarking(
                            $product[self::SKU_KEY],
                            $technique["miejsce_zdobienia"],
                            $technique["technika_zdobienia"],
                            $technique["wymiary_zdobienia"] . " mm",
                            null, // no valid images available
                            $technique["ilosc_kolorow"] > 1
                                ? collect()->range(1, $technique["ilosc_kolorow"])
                                    ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                                        "mod" => "*$i",
                                        "include_setup" => true,
                                    ]])
                                    ->toArray()
                                : null,
                            collect($marking["cennik"] ?? [])
                                ->mapWithKeys(fn ($p) => [$p["liczba_sztuk"] => [
                                    "price" => $p["cena_pln"],
                                    "flat" => boolval($p["ryczalt"]),
                                ]])
                                ->toArray(),
                            $marking["przygotowalnia_cena"]
                        );
                    }
                }

                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress (step)", (++$counter / $total) * 100);
            }

            if ($sync->product_import_enabled) {
                $this->deleteUnsyncedProducts($sync, $imported_ids);
            }

            $this->updateSynchStatus(self::SUPPLIER_NAME, "complete");
        }
        catch (\Exception $e)
        {
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product[self::PRIMARY_KEY], "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
    }

    private function getStockInfo(): Collection
    {
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "stocks.json", []);

        return $res->collect("products")
            ->map(fn($i) => $i["product"]);
    }

    private function getProductInfo(): Collection
    {
        Log::info(self::SUPPLIER_NAME . "> -- pulling products data. This may take a while...");
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "products.json", []);

        return $res->collect("products")
            ->map(fn($i) => $i["product"]);
    }

    private function getMarkingInfo(): Collection
    {
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "technics.json", []);

        return $res->collect();
    }

    private function processTabs(array $product) {
        $specification = collect([
            "material_wykonania;material_dodatkowy" => "Materiał podstawowy",
            "wymiary" => "Wymiary (szer./wys./gł.) [mm]",
            "kolor_podstawowy;kolor_dodatkowy" => "Kolorystyka",
            "customs_code" => "Kod celny",
        ])
            ->mapWithKeys(fn($label, $item) => [
                $label => collect(explode(";", $item))
                    ->map(fn($iitem) => $product[$iitem])
                    ->join(", ")
            ])
            ->toArray();
        $packing_for_specification = collect([
            "rodzaj_opakowania;material_opakowania" => "Opakowanie",
            "kolor_opakowania" => "Kolor opakowania",
        ])
            ->mapWithKeys(fn($label, $item) => [
                $label => collect(explode(";", $item))
                    ->map(fn($iitem) => $product["opakowania"][$iitem])
                    ->join(" / ")
            ])
            ->toArray();

        $packing_cells = collect([
            "opakowanie_jednostkowe" => "Opakowanie jednostkowe",
            "karton_wewnetrzny" => "Karton wewnętrzny",
            "karton_duzy" => "Karton duży",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $product["opakowania"][$item]])
            ->filter(fn($op) => !empty($op["ilosc"]))
            ->map(fn($op, $label) => [
                "heading" => $label,
                "type" => "table",
                "content" => [
                    "Waga brutto [kg]" => as_number($op["waga_brutto"]),
                    "Waga netto [kg]" => as_number($op["waga_netto"]),
                    "Długość [mm]" => as_number($op["waga_dlugosc"]),
                    "Szerokość [mm]" => as_number($op["waga_szerokosc"]),
                    "Wysokość [mm]" => as_number($op["waga_wysokosc"]),
                ]
            ])
            ->toArray();

        $markings_cells = collect($product["techniki_zdobienia"])
            ->map(fn($technique) => [
                [
                    "heading" => $technique["technika_zdobienia"],
                    "type" => "table",
                    "content" => [
                        "Miejsce zdobienia" => $technique["miejsce_zdobienia"],
                        "Makymalna wielkość zdobienia (szer./wys.) [mm]" => $technique["maksymalny_rozmiar_logo"],
                        "Maksymalny obszar zdobienia (szer./wys.) [mm]" => $technique["wymiary_zdobienia"],
                        "Maksymalna ilość kolorów" => $technique["ilosc_kolorow"],
                    ]
                ],
                ["type" => "tiles", "content" => ["Szablon zdobienia (pobierz PDF)" => $technique["template_url"]]],
            ])
            ->flatten(1)
            ->toArray();

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
                "cells" => [
                    ["type" => "table", "content" => $specification],
                    ["type" => "table", "content" => $packing_for_specification],
                    ["type" => "tiles", "content" => ["Specyfikacja produktu" => "https://www.par.com.pl/product_specifications/$product[id].pdf"]]
                ],
            ],
            [
                "name" => "Opakowanie",
                "cells" => $packing_cells,
            ],
            [
                "name" => "Znakowanie",
                "cells" => $markings_cells,
            ],
        ]);
    }
}
