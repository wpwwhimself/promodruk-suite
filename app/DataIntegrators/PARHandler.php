<?php

namespace App\DataIntegrators;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class PARHandler extends ApiHandler
{
    #region constants
    private const URL = "https://www.par.com.pl/api/";
    private const SUPPLIER_NAME = "PAR";
    public function getPrefix(): string { return "R"; }
    private const PRIMARY_KEY = "id";
    public const SKU_KEY = "kod";
    public function getPrefixedId(string $original_sku): string { return $original_sku; }
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
            $imported_ids[] = $product[self::PRIMARY_KEY];

            if ($this->sync->current_external_id != null && $this->sync->current_external_id > $product[self::PRIMARY_KEY]) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$product[self::PRIMARY_KEY], $product[self::PRIMARY_KEY]);

            if ($this->sync->product_import_enabled) {
                $this->prepareAndSaveProductData(compact("product"));
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
                $imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->sync->product_import_enabled) $this->deleteUnsyncedProducts($imported_ids);

        $this->reportSynchCount($counter, $total);
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        $products = $this->getProductInfo()->sortBy(self::PRIMARY_KEY);
        $stocks = ($stock) ? $this->getStockInfo()->sortBy(self::PRIMARY_KEY) : collect();
        $markings = ($marking) ? $this->getMarkingInfo() : collect();

        return compact(
            "products",
            "stocks",
            "markings",
        );
    }

    private function getStockInfo(): Collection
    {
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "stocks.json", [])
            ->throwUnlessStatus(200);

        return $res->collect("products")
            ->map(fn($i) => $i["product"]);
    }

    private function getProductInfo(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling products data. This may take a while...");
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "products.json", [])
            ->throwUnlessStatus(200);

        return $res->collect("products")
            ->map(fn($i) => $i["product"])
            ->filter(fn ($p) => Str::startsWith($p[self::SKU_KEY], $this->getPrefix()));
    }

    private function getMarkingInfo(): Collection
    {
        $res = Http::acceptJson()
            ->timeout(300)
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "technics.json", [])
            ->throwUnlessStatus(200);

        return $res->collect();
    }
    #endregion

    #region processing
    /**
     * @param array $data product
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "product" => $product,
        ] = $data;

        $this->saveProduct(
            $product[self::SKU_KEY],
            $product[self::PRIMARY_KEY],
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
            $this->getPrefix(),
            $this->processTabs($product),
            collect($product["kategorie"])->first()["name"],
            $product["kolor_podstawowy"],
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

        $stock = $stocks->firstWhere(self::PRIMARY_KEY, $product[self::PRIMARY_KEY]);
        if ($stock) $this->saveStock(
            $product[self::SKU_KEY],
            $stock["stan_magazynowy"],
            $stock["ilosc_dostawy"],
            isset($stock["data_dostawy"]) ? Carbon::parse($stock["data_dostawy"]) : null
        );
        else $this->saveStock($product[self::SKU_KEY], 0);
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

        foreach ($product["techniki_zdobienia"] as $technique) {
            $marking = $markings->firstWhere("id", $technique["technic_id"]);
            $this->saveMarking(
                $product[self::SKU_KEY],
                $technique["miejsce_zdobienia"],
                $technique["technika_zdobienia"],
                $technique["maksymalny_rozmiar_logo"] . " mm",
                null, // no valid images available
                $technique["ilosc_kolorow"] > 1
                    ? collect()->range(1, $technique["ilosc_kolorow"])
                        ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                            "mod" => "*".($i * $marking["color_ratio"] / 100),
                            "include_setup" => true,
                            "setup_mod" => "*$i",
                        ]])
                        ->toArray()
                    : null,
                collect($marking["cennik"] ?? [])
                    ->sortBy("liczba_sztuk")
                    ->mapWithKeys(fn ($p) => [$p["liczba_sztuk"] => [
                        "price" => $p["cena_pln"],
                        "flat" => boolval($p["ryczalt"]),
                    ]])
                    ->toArray(),
                $marking["przygotowalnia_cena"]
            );
        }

        $this->deleteCachedUnsyncedMarkings();
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
    #endregion
}
