<?php

namespace App\DataIntegrators;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MaximHandler extends ApiHandler
{
    #region constants
    private const URL = "https://api.maxim.com.pl/Api/";
    private const SUPPLIER_NAME = "Maxim";
    public function getPrefix(): string { return "MX"; }
    private const PRIMARY_KEY = "IdTW";
    private const PRIMARY_KEY_STOCK = "IdTw";
    public const SKU_KEY = "KodKreskowy";
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
            "stocks" => $stocks,
            "params" => $params,
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

            if ($this->sync->current_external_id != null && $this->sync->current_external_id > $product[self::PRIMARY_KEY]
                || empty($product[self::SKU_KEY] ?? $product["Barcode"] ?? null)
            ) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$product[self::PRIMARY_KEY], $product[self::PRIMARY_KEY]);

            foreach ($product["Warianty"] ?? $product["Variants"] ?? [] as $variant) {
                $this->sync->addLog("in progress", 3, "downloading variant: ".($variant[self::SKU_KEY] ?? $product["Barcode"]));

                if ($this->sync->product_import_enabled) {
                    $this->prepareAndSaveProductData(compact("product", "variant", "params"));
                }

                if ($this->sync->stock_import_enabled) {
                    $this->prepareAndSaveStockData(compact("variant", "stocks"));
                }
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
        $products = $this->getProductData();
        $params = ($product) ? $this->getParamData() : collect();
        $stocks = ($stock) ? $this->getStockData() : collect();

        return compact(
            "products",
            "stocks",
            "params",
        );
    }
    private function getProductData(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling products data");
        $products = Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetProducts", [
                "lang" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect();
        $this->sync->addLog("pending (step)", 3, "found: " . $products->count());

        $this->sync->addLog("pending (info)", 2, "pulling packaging data");
        $products = $products->merge(
            Http::acceptJson()
                ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
                ->post(self::URL . "GetBoxes", [
                    "lang" => "pl",
                ])
                ->throwUnlessStatus(200)
                ->collect()
        );
        $this->sync->addLog("pending (step)", 3, "now at: " . $products->count());

        return $products->sortBy(self::PRIMARY_KEY);
    }
    private function getStockData(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling stock data");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetStock", [])
            ->throwUnlessStatus(200)
            ->collect();
    }
    private function getParamData(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling dictionaries");
        return Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetParams", [])
            ->throwUnlessStatus(200)
            ->collect();
    }
    #endregion

    #region processing
    /**
     * @param array $data product, variant, prices
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "product" => $product,
            "variant" => $variant,
            "params" => $params,
        ] = $data;

        $this->saveProduct(
            $variant[self::SKU_KEY] ?? $variant["Barcode"],
            $product[self::PRIMARY_KEY],
            $product["Nazwa"] ?? $product["Name"],
            $product["Opisy"]["PL"]["www"] ?? null,
            $product[self::SKU_KEY] ?? $product["Barcode"],
            null, // as_number($variant["CenaBazowa"]),
            (isset($variant["Zdjecia"]))
                ? collect($variant["Zdjecia"])->pluck("link")->toArray()
                : collect($product["Photos"])->pluck("URL")->toArray(),
            (isset($variant["Zdjecia"]))
                ? collect($variant["Zdjecia"])->pluck("link")->toArray()
                : collect($product["Photos"])->pluck("URL")->toArray(),
            $this->getPrefix(),
            (isset($variant["Slowniki"]))
                ? $this->processTabs($product, $variant, $params)
                : null,
            (isset($product["Kategorie"]))
                ? (implode(" | ", $product["Kategorie"]["KategorieB2B"] ?? []) ?: "-")
                : "Opakowania", // assuming english-labelled products are boxes
            (isset($variant["Slowniki"]))
                ? collect([
                    $this->getParam($params, "sl_Kolor", $variant["Slowniki"]["sl_Kolor"] ?? null),
                    $this->getParam($params, "sl_KolorFiltr", $variant["Slowniki"]["sl_KolorFiltr"] ?? null)
                ])
                    ->filter()
                    ->unique()
                    ->join("/")
                : null,
            source: self::SUPPLIER_NAME,
        );
    }

    /**
     * @param array $data variant, stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "variant" => $variant,
            "stocks" => $stocks,
        ] = $data;

        $stock = $stocks->firstWhere(self::PRIMARY_KEY_STOCK, $variant[self::PRIMARY_KEY]);
        if ($stock) {
            $next_delivery = collect($stock["Dostawy"])
                ->sortBy("Data")
                ->first();
            $this->saveStock(
                $this->getPrefixedId($variant[self::SKU_KEY] ?? $variant["Barcode"]),
                $stock["Stan"],
                $next_delivery["Ilosc"] ?? null,
                $next_delivery ? Carbon::parse($next_delivery["Data"]) : null
            );
        } else {
            $this->saveStock($this->getPrefixedId($variant[self::SKU_KEY] ?? $variant["Barcode"]), 0);
        }
    }

    /**
     * @param array $data ???
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        // not available yet

        $this->deleteCachedUnsyncedMarkings();
    }

    private function getParam(Collection $params, string $dictionary, ?int $key): string | null
    {
        if (empty($key)) return null;
        return $params[$dictionary][$key]["description"] ?? null;
    }

    private function processTabs(array $product, array $variant, Collection $params) {
        //! specification
        $specification = collect([
            "Pojemnosc" => "Pojemnosc [ml]",
            "Wysokosc" => "Wysokość [mm]",
            "Srednica" => "Średnica [mm]",
            "Waga" => "Waga [g]",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $variant[$item] ?? null])
            ->pipe(fn($col) => $col->merge([
                "Materiał" => $this->getParam($params, "sl_Material", $variant["Slowniki"]["sl_Material"] ?? null),
            ]))
            ->toArray();

        //! packaging
        // tbd

        //! markings
        //! no marking data found in API

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
                "cells" => [["type" => "table", "content" => array_filter($specification ?? [])]]
            ],
            // [
            //     "name" => "Pakowanie",
            //     "cells" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            // ],
            [
                "name" => "Znakowanie",
                "cells" => [["type" => "tiles", "content" => ["Standardowe powierzchnie nadruku" => "https://legacy.maxim.com.pl/pdf/".Str::replace("M", "M_", $product[self::SKU_KEY])."_1.pdf"]]],
            ],
        ]);
    }
}
