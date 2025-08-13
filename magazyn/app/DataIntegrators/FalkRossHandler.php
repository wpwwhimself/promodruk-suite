<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class FalkRossHandler extends ApiHandler
{
    #region constants
    private const URL_OPEN = "http://download.falk-ross.eu/";
    private const URL_CLIENTS = "https://{{}}:{{}}@ws.falk-ross.eu/";
    private const SUPPLIER_NAME = "FalkRoss";
    public function getPrefix(): string { return "FR"; }
    private const PRIMARY_KEY = "style_nr";
    public const SKU_KEY = "style_nr";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }

    private string $style_version;
    private array $imported_ids;
    #endregion

    #region auth
    public function authenticate(): void
    {
        // no auth required here
    }

    private function getAuthenticatedUrlClients(): string
    {
        return Str::replaceArray(
            "{{}}",
            [
                env("FR_USERNAME"),
                env("FR_PASSWORD"),
            ],
            self::URL_CLIENTS
        );
    }
    #endregion

    #region main
    public function downloadAndStoreAllProductData(): void
    {
        if ($this->limit_to_module == "marking") {
            $this->sync->addLog("complete", 1, "Synchronization unavailable for $this->limit_to_module");
            return;
        }

        $this->sync->addLog("pending", 1, "Synchronization started" . ($this->limit_to_module ? " for " . $this->limit_to_module : ""), $this->limit_to_module);

        $counter = 0;
        $total = 0;

        [
            "ids" => $ids,
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        $total = $ids->count();
        $this->imported_ids = [];

        foreach ($ids as [$sku, $external_id]) {
            if ($this->sync->current_module_data["current_external_id"] != null && $this->sync->current_module_data["current_external_id"] > $external_id) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$sku, $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("sku", "products", "prices"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            // if ($this->canProcessModule("marking")) {
            //     $this->prepareAndSaveMarkingData(compact(...));
            // }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($this->imported_ids);
                $this->imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->canProcessModule("product")) $this->deleteUnsyncedProducts($this->imported_ids);

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

        [$products, $prices] = ($product) ? $this->getProductInfo() : [collect(), collect()];
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        $markings = ($marking) ? $this->getMarkingInfo() : collect();

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                $p->{self::SKU_KEY} ?? $p["sku"],
                $p->{self::PRIMARY_KEY} ?? $p["sku"],
            ]);

        return compact(
            "ids",
            "products",
            "prices",
            "stocks",
            "markings",
        );
    }

    private function getProductInfo(): array
    {
        // prices
        $prices = Http::accept("text/csv")
            ->get($this->getAuthenticatedUrlClients() . "ws/run/price.pl", [
                "format" => "csv",
                "style" => "",
                "action" => "get_price",
            ])
            ->throwUnlessStatus(200)
            ->body();
        $prices = array_map(
            fn($ln) => str_getcsv($ln, ";"),
            array_filter(explode("\n", $prices))
        );

        $header = $prices[0];
        $prices = collect($prices)->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        // products
        /** plan
         * loop over all products from stylelist
         * bash it together in one variable
         */
        $style_list = Http::accept("text/xml")
            ->get(self::URL_OPEN . "ws/falkross-stylelist.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $style_list = new SimpleXMLElement($style_list);
        $this->style_version = (string) $style_list->file_version;

        $products = collect($style_list->xpath("//style[url_style_xml]"))
            ->sort(fn ($a, $b) => (int) $a->{self::PRIMARY_KEY} <=> (int) $b->{self::PRIMARY_KEY});

        return [$products, $prices];
    }
    private function getSingleProductInfo(SimpleXMLElement $product): ?SimpleXMLElement
    {
        $res = Http::accept("text/xml")
            ->get((string) $product->url_style_xml, []);
        if ($res->status() == 404) {
            return null;
        }

        $style_data = $res->body();
        $style_data = (new SimpleXMLElement($style_data))->xpath("//style")[0];
        return $style_data;
    }

    private function getStockInfo(): Collection
    {
        $res = Http::accept("text/csv")
            ->get($this->getAuthenticatedUrlClients() . "webservice/R03_000/stockinfo/falkross_de.csv", [])
            ->throwUnlessStatus(200)
            ->body();
        $res = collect(explode("\r\n", $res))
            ->skip(1)
            ->filter() // remove empty lines
            ->map(fn($row) => array_combine(["sku", "quantity_pl", "quantity_de", "quantity_manufacturer"], str_getcsv($row, ";")))
            ->sortBy("sku");

        return $res;
    }

    private function getMarkingInfo(): Collection
    {
        return collect();
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products, prices
     */
    public function prepareAndSaveProductData(array $data): ?array
    {
        $ret = [];

        [
            "sku" => $sku,
            "products" => $products,
            "prices" => $prices,
        ] = $data;

        $product = $products->firstWhere(fn ($p) => (string) $p->{self::SKU_KEY} == $sku);
        $product_family_details = $this->getSingleProductInfo($product);
        if (empty($product_family_details)) {
            $this->sync->addLog("in progress (step)", 2, "Product missing");
            return null;
        }

        $variants = collect($product->xpath("//sku_list/sku"))
            ->groupBy(fn ($var) => (string) $var->sku_color_code);
        $imgs = collect($product->xpath("//style_picture_list/style_picture/url"))
            ->map(fn ($img) => (string) $img);

        $imported_ids = [];
        $i = 0;

        foreach ($variants as $color_code => $size_variants) {
            $variant = $size_variants->first();
            $prepared_sku = $product->{self::PRIMARY_KEY} . "-" . $color_code;

            $this->sync->addLog("in progress", 3, "saving product variant ".$prepared_sku."(".($i++ + 1)."/".count($variants).")", (string) $product->{self::PRIMARY_KEY});
            $ret[] = $this->saveProduct(
                $this->getPrefixedId($prepared_sku),
                $prepared_sku,
                (string) $product->style_name->language->pl,
                (string) $product->style_description->language->pl,
                $this->getPrefixedId($product->{self::PRIMARY_KEY}),
                null, // as_number($prices->firstWhere("artnr", (string) $variant->sku_artnum)["your_price"] ?? null), //? temporally disabled
                [[(string) $variant->sku_color_picture_url], $imgs],
                [[(string) $variant->sku_color_picture_url], $imgs],
                $this->getPrefix(),
                $this->processTabs($product),
                collect($product->xpath("//style_category_list/style_category_main/style_category_sub/language/pl"))
                    ->map(fn($c) => (string) $c)
                    ->first(),
                (string) $variant->sku_color_name,
                source: self::SUPPLIER_NAME,
                sizes: $size_variants->map(fn($v) => [
                    "size_name" => (string) $v->sku_size_name,
                    "size_code" => (string) $v->sku_size_code,
                    "full_sku" => $this->getPrefixedId($v->sku_artnum),
                ])
                    ->toArray(),
                extra_filtrables: array_filter([
                    "Marka" => [(string) $product->brand_name],
                    "Model/Płeć" => collect($product->xpath("//style_gender_group_list/style_gender_group/pl"))
                        ->map(fn($f) => (string) $f)
                        ->toArray(),
                    "Rodzaj" => collect($product->xpath("/style_category_sub/language/pl"))
                        ->map(fn($f) => (string) $f)
                        ->toArray(),
                    "Dodatkowe właściwości" => collect($product->xpath("//style_product_group_list/style_product_group/pl"))
                        ->map(fn($g) => (string) $g)
                        ->toArray(),
                    "Gramatura materiału" => collect($product->xpath("//style_weight_group_list/style_weight_group/pl"))
                        ->map(fn($g) => (string) $g)
                        ->toArray(),
                    "Rękawy" => collect($product->xpath("//style_sleeve_group_list/style_sleeve_group/pl"))
                        ->map(fn($f) => (string) $f)
                        ->toArray(),
                    "Dodatki" => collect($product->xpath("//style_details_group_list/style_details_group/pl"))
                        ->map(fn($g) => (string) $g)
                        ->toArray(),
                ]),
            );

            $imported_ids[] = $prepared_sku;
        }

        // tally imported IDs
        $this->imported_ids = array_merge($this->imported_ids, $imported_ids);

        return $ret;
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

        $stock = $stocks->firstWhere("sku", $sku);

        return $this->saveStock(
            $this->getPrefixedId($sku),
            $stock["quantity_pl"] + $stock["quantity_de"] + $stock["quantity_manufacturer"] ?: 0,
        );
    }

    /**
     * @param array $data ...
     */
    public function prepareAndSaveMarkingData(array $data): null
    {
        $this->deleteCachedUnsyncedMarkings();

        abort(501);
    }

    private function processTabs(SimpleXMLElement $product) {
        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return array_filter([
            // [
            //     "name" => "Kluczowe funkcje",
            //     "cells" => [["type" => "table", "content" => array_filter($key_features ?? [])]],

            // ],
            [
                "name" => "Pliki do pobrania",
                "cells" => [["type" => "tiles", "content" => [
                    "Rozmiarówka" => (string) $product->sizespec_download_link,
                ]]],
            ],
        ]);
    }
    #endregion
}
