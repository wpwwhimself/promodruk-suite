<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\map;

class PARHandler extends ApiHandler
{
    private const URL = "https://www.par.com.pl/api/";
    private const SUPPLIER_NAME = "PAR";
    public function getPrefix(): string { return "R"; }

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $counter = 0;
        $total = 0;

        if ($sync->product_import_enabled)
            Log::debug("-- pulling product data. This may take a while...");
            $products = $this->getProductInfo()->sortBy("id");
        if ($sync->stock_import_enabled)
            $stocks = $this->getStockInfo()->sortBy("id");

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($sync->current_external_id != null && $sync->current_external_id > $product["id"]) {
                    $counter++;
                    continue;
                }

                Log::debug("-- downloading product $product[id]: " . $product["kod"]);
                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["id"]]);

                if ($sync->product_import_enabled) {
                    $this->saveProduct(
                        $product["kod"],
                        $product["nazwa"],
                        $product["opis"],
                        Str::beforeLast($product["kod"], "."),
                        $product["cena_pln"],
                        collect($product["zdjecia"])->sortBy("zdjecie")->map(fn($i) => "https://www.par.com.pl". $i["zdjecie"])->toArray(),
                        collect($product["zdjecia"])
                            ->sortBy("zdjecie")
                            ->map(fn($i) => "https://www.par.com.pl". $i["zdjecie"])
                            ->map(fn($i) => str_replace("/full", "/pelne", $i))
                            ->map(fn($i) => str_replace(".jpg", ".png", $i))
                            ->toArray(),
                        $product["kod"],
                        $this->processTabs($product),
                        collect($product["kategorie"])->first()["name"],
                        $product["kolor_podstawowy"]
                    );
                }

                if ($sync->stock_import_enabled) {
                    $stock = $stocks->firstWhere("id", $product["id"]);
                    if ($stock) $this->saveStock(
                        $product["kod"],
                        $stock["stan_magazynowy"],
                        $stock["ilosc_dostawy"],
                        isset($stock["data_dostawy"]) ? Carbon::parse($stock["data_dostawy"]) : null
                    );
                    else $this->saveStock($product["kod"], 0);
                }

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null, "product_import_enabled" => false]);
        }
        catch (\Exception $e)
        {
            Log::error("-- Error in " . self::SUPPLIER_NAME . ": " . $e->getMessage(), ["exception" => $e]);
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
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "products.json", []);

        return $res->collect("products")
            ->map(fn($i) => $i["product"]);
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
                ["type" => "tiles", "content" => ["Szablon zdobienia" => $technique["template_url"]]],
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
