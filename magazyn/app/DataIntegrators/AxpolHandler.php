<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

ini_set("memory_limit", "512M");

class AxpolHandler extends ApiHandler
{
    #region constants
    private const URL = "https://axpol.com.pl/api/b2b-api/";
    private const SUPPLIER_NAME = "Axpol";
    public function getPrefix(): array { return ["V", "P", "T"]; }
    private const PRIMARY_KEY = "productId";
    public const SKU_KEY = "CodeERP";
    private const USER_AGENT = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0. 2272.118 Safari/537.36";
    public function getPrefixedId(string $original_sku): string { return $original_sku; }
    private const PRICELIST_FILENAME = "axpol_print_pricelist_PL.xml";
    #endregion

    #region auth
    public function authenticate(): void
    {
        $res = Http::acceptJson()
            ->withUserAgent(self::USER_AGENT)
            ->asForm()
            ->post(self::URL . "", [
                "method" => "Customer.Login",
                "key" => env("AXPOL_API_SECRET"),
                "params[username]" => env("AXPOL_API_LOGIN"),
                "params[password]" => env("AXPOL_API_PASSWORD"),
            ])
            ->throwUnlessStatus(200)
            ->collect("data");

        session([
            "axpol_uid" => $res["uid"],
            "axpol_token" => $res["jwt"],
        ]);
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
            "markings" => $markings,
            "prices" => $prices,
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

            if ($this->sync->current_module_data["current_external_id"] != null && $this->sync->current_module_data["current_external_id"] > $external_id) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("sku", "products", "markings", "prices"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            if ($this->canProcessModule("marking")) {
                $this->prepareAndSaveMarkingData(compact("sku", "markings", "prices"));
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

        $products = ($product || $marking) ? $this->getProductInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        [$markings, $prices] = ($product || $marking) ? $this->getMarkingInfo() : [collect(),collect()];

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                $p[self::SKU_KEY],
                $p[self::PRIMARY_KEY] ?? $p[self::SKU_KEY],
            ]);

        return compact(
            "ids",
            "products",
            "stocks",
            "markings",
            "prices",
        );
    }

    private function getProductInfo(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling products data. This may take a while...");
        $data = collect();
        $is_last_page = false;
        $page = 1;

        while (!$is_last_page) {
            $this->sync->addLog("pending (step)", 3, "page " . $page);
            $res = Http::acceptJson()
                ->withUserAgent(self::USER_AGENT)
                ->withToken(session("axpol_token"))
                ->timeout(300)
                ->get(self::URL . "", [
                    "key" => env("AXPOL_API_SECRET"),
                    "uid" => session("axpol_uid"),
                    "method" => "Product.List",
                    "params[date]" => "1970-01-01 00:00:00",
                    "params[limit]" => 1000, // API limits up to 1000
                    "params[offset]" => 1000 * ($page++ - 1),
                ])
                ->throwUnlessStatus(200)
                ->collect("data")
                ->filter(fn($p) => Str::startsWith($p[self::SKU_KEY], $this->getPrefix()))
                ->filter(fn($p) => !Str::contains($p["TitlePL"], "test", true));
            $data = $data->merge($res);
            $is_last_page = $res->count() == 0;
        }

        return $data->sortBy(self::PRIMARY_KEY);
    }

    private function getStockInfo(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "pulling stock data. This may take a while...");
        $data = collect();
        $is_last_page = false;
        $page = 1;

        while (!$is_last_page) {
            $this->sync->addLog("pending (step)", 3, "page " . $page);
            $res = Http::acceptJson()
                ->withUserAgent(self::USER_AGENT)
                ->withToken(session("axpol_token"))
                ->timeout(300)
                ->get(self::URL . "", [
                    "key" => env("AXPOL_API_SECRET"),
                    "uid" => session("axpol_uid"),
                    "method" => "Stock.List",
                    "params[date]" => "1970-01-01 00:00:00",
                    "params[limit]" => 1000, // API limits up to 1000
                    "params[offset]" => 1000 * ($page++ - 1),
                ])
                ->throwUnlessStatus(200)
                ->collect("data")
                ->filter(fn($p) => Str::startsWith($p[self::SKU_KEY], $this->getPrefix()));
            $data = $data->merge($res);
            $is_last_page = $res->count() == 0;
        }

        return $data->sortBy(self::SKU_KEY);
    }

    private function getMarkingInfo(): array
    {
        $this->sync->addLog("pending (info)", 2, "pulling markings data. This may take a while...");
        $markings = collect();
        $is_last_page = false;
        $page = 1;

        while (!$is_last_page) {
            $this->sync->addLog("pending (step)", 3, "page " . $page);
            $res = Http::acceptJson()
                ->withUserAgent(self::USER_AGENT)
                ->withToken(session("axpol_token"))
                ->timeout(300)
                ->get(self::URL . "", [
                    "key" => env("AXPOL_API_SECRET"),
                    "uid" => session("axpol_uid"),
                    "method" => "Printing.List",
                    "params[date]" => "1970-01-01 00:00:00",
                    "params[limit]" => 1000, // API limits up to 1000
                    "params[offset]" => 1000 * ($page++ - 1),
                ])
                ->throwUnlessStatus(200)
                ->collect("data")
                ->filter(fn($p) => Str::startsWith($p[self::SKU_KEY], $this->getPrefix()))
                ->sortBy(self::PRIMARY_KEY);
            $markings = $markings->merge($res);
            $is_last_page = $res->count() == 0;
        }

        $this->sync->addLog("pending (info)", 3, "pulling markings pricelist...");
        try
        {
            $prices = Storage::disk("axpol-sftp")
                ->get(self::PRICELIST_FILENAME);
            Storage::disk("public")
                ->put("integrators/" . self::PRICELIST_FILENAME, $prices);
        }
        catch (\Exception $e)
        {
            $this->sync->addLog("pending (info)", 4, "failed, using backup...");
            $prices = Storage::disk("public")
                ->get("integrators/" . self::PRICELIST_FILENAME);

            if (!$prices) {
                throw new \Exception(self::SUPPLIER_NAME . "> ---- failed, no backup available");
            }
        }

        $prices = collect($this->mapXml(fn($p) => $p, new SimpleXMLElement($prices)));

        return [$markings, $prices];
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products, markings, prices
     */
    public function prepareAndSaveProductData(array $data): Product
    {
        [
            "sku" => $sku,
            "products" => $products,
            "markings" => $markings,
            "prices" => $prices,
        ] = $data;

        $product = $products->firstWhere(self::SKU_KEY, $sku);

        return $this->saveProduct(
            $product[self::SKU_KEY],
            $product[self::PRIMARY_KEY],
            $product["TitlePL"],
            $product["DescriptionPL"],
            ($product[self::SKU_KEY][0] == "P")
                ? substr($product[self::SKU_KEY], 0, 7)
                : Str::beforeLast($product[self::SKU_KEY], "-"),
            as_number($product["NetPricePLN"]),
            collect($product["Foto"] ?? [])->sort()->map(fn($file, $i) => "https://axpol.com.pl/files/" . ($i == 0 ? "fotov" : "foto_add_view") . "/". $file)->toArray(),
            collect($product["Foto"] ?? [])->sort()->map(fn($file, $i) => "https://axpol.com.pl/files/" . ($i == 0 ? "fotom" : "foto_add_medium") . "/". $file)->toArray(),
            Str::substr($product[self::SKU_KEY], 0, 1),
            $this->processTabs($product, $markings[$product["productId"]] ?? null),
            implode(" > ", [$product["MainCategoryPL"], $product["SubCategoryPL"]]),
            $product["ColorPL"] ?? null,
            source: self::SUPPLIER_NAME,
            manipulation_cost: ((float) $prices->firstWhere(fn($p) => $p->print_code == $product["HandlingCost"])?->print_price) ?? 0,
        );
    }

    /**
     * @param array $data sku, stocks
     */
    public function prepareAndSaveStockData(array $data): Stock
    {
        [
            "sku" => $sku,
            "stocks" => $stocks,
        ] = $data;

        $stock = $stocks->firstWhere(self::SKU_KEY, $sku);

        return $this->saveStock(
            $sku,
            as_number($stock["InStock"]) + (Str::replace(" ", "", $stock["Days"]) == "1-2" ? as_number($stock["onOrder"]) : 0),
            as_number($stock["nextDelivery"]),
            Carbon::today()->addMonths(2)->firstOfMonth() // todo znaleźć
        );
    }

    /**
     * @param array $data sku, markings, prices
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "markings" => $markings,
            "prices" => $prices,
        ] = $data;

        $markings = $markings->firstWhere(fn($p) => $p[self::SKU_KEY] == $sku)["Print"] ?? [];

        foreach ($markings as $marking) {
            foreach ($marking["Technique"] ?? [] as $technique) {
                $technique_prices_by_mod = $prices->filter(fn($p) => $p->print_code == $technique)
                    ->groupBy(fn($p) => $p->setup_Qty);

                if ($technique_prices_by_mod->isEmpty()) continue;

                $technique_prices_1 = $technique_prices_by_mod->first();

                $ret[] =$this->saveMarking(
                    $sku,
                    $marking["Position"],
                    Str::replace("_", " ", (string) $technique_prices_1->first()->print_name),
                    $marking["Size"] . " mm",
                    null, // no images available
                    $technique_prices_by_mod->count() > 1
                        ? $technique_prices_by_mod->mapWithKeys(fn($technique_prices, $color_count) => [
                            "$color_count kolor" . ($color_count >= 5 ? "ów" : ($color_count == 1 ? "" : "y")) => [
                                "mod" => "*$color_count",
                                "include_setup" => true,
                            ]
                        ])->toArray()
                        : null,
                    $technique_prices_1
                        ->mapWithKeys(fn($p) => [(string) $p->from_Qty => [
                            "price" => (float) $p->print_price,
                        ]])
                        ->sortKeys()
                        ->toArray(),
                    (float) $technique_prices_1->first()->setup_cost
                );
            }
        }

        $this->deleteCachedUnsyncedMarkings();

        return $ret;
    }

    private function processTabs(array $product, ?array $marking = null) {
        $specification = collect([
            "Dimensions" => "Wymiary",
            "MaterialPL" => "Materiał",
            "Page" => "Strona w katalogu",
            "ColorPL" => "Kolor",
            "Film" => "Film",
            "Video360" => "Video360",

            "CountryOfOrigin" => "Kraj pochodzenia",
            "CustomCode" => "Kod PCN",
            "ItemWeightG" => "Waga produktu (g)",
            "EAN" => "EAN",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $product[$item] ?? null])
            ->toArray();

        $packing = collect([
            "IndividualPacking" => "Pakowanie indywidualne",
            "ExportCtnQty" => "Ilość w kartonie zbiorczym",
            "CtnDimensions" => "Wymiary kartonu zbiorczego",
            "CtnWeightKG" => "Waga kartonu zbiorczego",
        ])
            ->mapWithKeys(fn($label, $item) => [$label => $product[$item] ?? null])
            ->toArray();

        $marking_data = collect($marking["Print"] ?? null)
            ->filter(fn($p) => !empty($p["Position"]))
            ->mapWithKeys(fn($variant) => [$variant["Position"] => implode("\n", [
                $variant["Size"],
                implode(", ", $variant["Technique"] ?? []),
            ])])
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
                "cells" => [["type" => "table", "content" => $specification]],
            ],
            [
                "name" => "Pakowanie",
                "cells" => [["type" => "table", "content" => $packing]],
            ],
            !$marking_data ? null : [
                "name" => "Znakowanie",
                "cells" => [
                    ["type" => "table", "content" => $marking_data],
                    ["type" => "tiles", "content" => ["Print info" => "https://axpol.com.pl/files/image/print_info_pl.jpg"]],
                ]
            ],
        ]);
    }
    #endregion
}
