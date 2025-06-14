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
        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        [
            "ids" => $ids,
            "products" => $products,
            "stocks" => $stocks,
            "params" => $params,
            "printing_options" => $printing_options,
            "painting_options" => $painting_options,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $ids->count();
        $imported_ids = [];

        foreach ($ids as [$sku, $external_id]) {
            $imported_ids[] = $external_id;

            if ($this->sync->current_external_id != null && $this->sync->current_external_id > $external_id
                || empty($sku)
            ) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("external_id", "products", "params"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("external_id", "products", "stocks"));
            }

            if ($this->canProcessModule("marking")) {
                $this->prepareAndSaveMarkingData(compact("external_id", "products", "printing_options", "painting_options"));
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

        $products = $this->getProductData();
        $params = ($product) ? $this->getParamData() : collect();
        $stocks = ($stock) ? $this->getStockData() : collect();
        [$printing_options, $painting_options] = ($marking) ? $this->getMarkingData() : [collect(), collect()];

        $ids = $products->map(fn ($p) => [
            $p[self::SKU_KEY] ?? $p["Barcode"] ?? null,
            $p[self::PRIMARY_KEY],
        ]);

        return compact(
            "ids",
            "products",
            "stocks",
            "params",
            "printing_options",
            "painting_options",
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
    private function getMarkingData(): array
    {
        $this->sync->addLog("pending (info)", 2, "pulling general marking data");

        $printing_options = Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetPrintingOptions", [
                "lang" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect();
        $painting_options = Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetPaintingOptions", [
                "lang" => "pl",
            ])
            ->throwUnlessStatus(200)
            ->collect();

        return [$printing_options, $painting_options];
    }
    private function getMarkingDataForExternalId(int $external_id): array
    {
        $printing_options = Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetPrintingOptionsForProduct", [
                "lang" => "pl",
                "idtw" => $external_id,
            ])
            ->throwUnlessStatus(200)
            ->collect();
        $painting_options = Http::acceptJson()
            ->withHeader("X-API-KEY", env("MAXIM_API_KEY"))
            ->post(self::URL . "GetPaintingOptionsForProduct", [
                "lang" => "pl",
                "idtw" => $external_id,
            ])
            ->throwUnlessStatus(200)
            ->collect();

        return [$printing_options, $painting_options];
    }
    #endregion

    #region processing
    /**
     * @param array $data external_id, products, params
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "external_id" => $external_id,
            "products" => $products,
            "params" => $params,
        ] = $data;

        $product = $products->firstWhere(self::PRIMARY_KEY, $external_id);
        $variants = $product["Warianty"] ?? $product["Variants"];

        foreach ($variants as $variant) {
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
    }

    /**
     * @param array $data external_id, products, stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "external_id" => $external_id,
            "products" => $products,
            "stocks" => $stocks,
        ] = $data;

        $product = $products->firstWhere(self::PRIMARY_KEY, $external_id);
        $variants = $product["Warianty"] ?? $product["Variants"];

        foreach ($variants as $variant) {
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
    }

    /**
     * @param array $data external_id, products, printing_options, painting_options
     */
    public function prepareAndSaveMarkingData(array $data): void
    {
        [
            "external_id" => $external_id,
            "products" => $products,
            "printing_options" => $printing_options,
            "painting_options" => $painting_options,
        ] = $data;

        $product = $products->firstWhere(self::PRIMARY_KEY, $external_id);
        [$printing_options_for_product, $painting_options_for_product] = $this->getMarkingDataForExternalId($product[self::PRIMARY_KEY]);

        foreach (["printing", "painting"] as $method) {
            $options_for_product_var = $method."_options_for_product";
            foreach ($$options_for_product_var as $marking) {
                foreach ($product["Warianty"] as $variant) {
                    $this->saveMarking(
                        $variant[self::SKU_KEY],
                        $marking["position"],
                        $marking["techName"],
                        implode("x", [$marking["width"], $marking["height"]]),
                        null,
                        null, //todo ustalić
                        null, //todo ustalić
                        null, //todo ustalić
                    );
                }
            }
        }

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
    #endregion
}
