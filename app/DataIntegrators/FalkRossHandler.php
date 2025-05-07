<?php

namespace App\DataIntegrators;

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
    public const SKU_KEY = "style_nr_dot";
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
        $this->sync->addLog("pending", 1, "Synchronization started");

        $counter = 0;
        $total = 0;

        [
            "style_list" => $style_list,
            "prices" => $prices,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $this->downloadData(
            $this->sync->product_import_enabled,
            $this->sync->stock_import_enabled,
            $this->sync->marking_import_enabled
        );

        $this->sync->addLog("pending (info)", 1, "Ready to sync");

        //* FR-specific product list building
        // this has to be done one by one because it's easier on the resources
        $products = collect($style_list->xpath("//style[url_style_xml]"))
            ->sort(fn ($a, $b) => (int) $a->{self::PRIMARY_KEY} <=> (int) $b->{self::PRIMARY_KEY});

        $total = $products->count();
        $this->imported_ids = [];

        foreach ($products as $product) {
            if ($this->sync->current_external_id != null && $this->sync->current_external_id > intval($product->{self::PRIMARY_KEY})) {
                $counter++;
                continue;
            }

            $this->sync->addLog("in progress", 2, "Downloading product: ".$product[self::PRIMARY_KEY], $product[self::PRIMARY_KEY]);

            $product_family_details = $this->getSingleProductInfo($product);
            if (empty($product_family_details)) {
                $this->sync->addLog("in progress (step)", 2, "Product missing", (++$counter / $total) * 100);
                continue;
            }

            if ($this->sync->product_import_enabled) {
                $this->prepareAndSaveProductData(compact("product_family_details", "prices"));
            }

            if ($this->sync->stock_import_enabled) {
                $this->prepareAndSaveStockData(compact("product_family_details", "stocks"));
            }

            if ($this->sync->marking_import_enabled) {
                // $this->prepareAndSaveMarkingData(compact("product", "markings"));
            }

            $this->sync->addLog("in progress (step)", 2, "Product downloaded", (++$counter / $total) * 100);

            $started_at ??= now();
            if ($started_at < now()->subMinutes(1)) {
                if ($this->sync->product_import_enabled) $this->deleteUnsyncedProducts($this->imported_ids);
                $this->imported_ids = [];
                $started_at = now();
            }
        }

        if ($this->sync->product_import_enabled) $this->deleteUnsyncedProducts($this->imported_ids);

        $this->reportSynchCount($counter, $total);
    }
    #endregion

    #region download
    public function downloadData(bool $product, bool $stock, bool $marking): array
    {
        [$style_list, $prices] = $this->getProductInfo();
        $stocks = ($stock) ? $this->getStockInfo() : collect();
        $markings = ($marking) ? $this->getMarkingInfo() : collect();

        return compact(
            "style_list",
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
        $res = Http::accept("text/xml")
            ->get(self::URL_OPEN . "ws/falkross-stylelist.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $res = new SimpleXMLElement($res);
        $this->style_version = (string) $res->file_version;

        return [$res, $prices];
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
            ->map(fn($row) => array_combine(["sku", "quantity_pl", "quantity_de", "quantity_manufacturer"], str_getcsv($row, ";")));

        return $res;
    }

    private function getMarkingInfo(): Collection
    {
        return collect();
    }
    #endregion

    #region processing
    /**
     * @param array $data product, prices
     */
    public function prepareAndSaveProductData(array $data): void
    {
        [
            "product_family_details" => $product,
            "prices" => $prices,
        ] = $data;

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
            $this->saveProduct(
                $this->getPrefixedId($prepared_sku),
                $prepared_sku,
                (string) $product->style_name->language->pl,
                (string) $product->style_description->language->pl,
                $this->getPrefixedId($product->{self::PRIMARY_KEY}),
                null, // as_number($prices->firstWhere("artnr", (string) $variant->sku_artnum)["your_price"] ?? null), //? temporally disabled
                [[(string) $variant->sku_color_picture_url], $imgs],
                [[(string) $variant->sku_color_picture_url], $imgs],
                $this->getPrefix(),
                null, // $this->processTabs($product), //todo dodać taby
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
    }

    /**
     * @param array $data product, stocks
     */
    public function prepareAndSaveStockData(array $data): void
    {
        [
            "product_family_details" => $product,
            "stocks" => $stocks,
        ] = $data;

        $variants = $product->xpath("//sku_list/sku");

        foreach ($variants as $variant) {
            $stock = $stocks->firstWhere("sku", (string) $variant->sku_artnum);
            if ($stock) $this->saveStock(
                $this->getPrefixedId($variant->sku_artnum),
                $stock["quantity_pl"] + $stock["quantity_de"] + $stock["quantity_manufacturer"]
            );
            else $this->saveStock($this->getPrefixedId($variant->sku_artnum), 0);
        }
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

        //

        $this->deleteCachedUnsyncedMarkings();
    }

    private function processTabs(SimpleXMLElement $product) {
        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification = [
            "Rozmiar produktu" => $product->attributes->size?->__toString(),
            "Materiał" => implode(", ", $this->mapXml(fn ($m) => $m->name?->__toString(), $product->materials->material)),
            "Kraj pochodzenia" => $product->origincountry->name?->__toString(),
            "Marka" => $product->brand->name?->__toString(),
            "Waga" => $product->attributes->weight?->__toString(),
            "Kolor" => $product->color->name?->__toString(),
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
            "Opakowanie" => $product->packages->package?->name?->__toString(),
            "Małe opakowanie (szt.)" => $product->attributes->pack_small?->__toString(),
            "Duże opakowanie (szt.)" => $product->attributes->pack_large?->__toString(),
        ];

        //! markings
        $markings["Grupy i rozmiary znakowania"] = implode("\n", $this->mapXml(fn ($m) => $m->name?->__toString(), $product->markgroups));

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
    #endregion
}
