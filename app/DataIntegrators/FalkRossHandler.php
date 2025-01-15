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
    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        $this->updateSynchStatus(self::SUPPLIER_NAME, "pending");

        $counter = 0;
        $total = 0;

        [
            "style_list" => $style_list,
            "prices" => $prices,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $this->downloadData(
            $sync->product_import_enabled,
            $sync->stock_import_enabled,
            $sync->marking_import_enabled
        );

        try
        {
            //* FR-specific product list building
            // this has to be done one by one because it's easier on the resources
            $products = collect($style_list->xpath("//style[url_style_xml]"))
                ->sort(fn ($a, $b) => (int) $a->{self::PRIMARY_KEY} <=> (int) $b->{self::PRIMARY_KEY});

            $total = $products->count();
            $imported_ids = [];

            foreach ($products as $product) {
                $imported_ids[] = $this->getPrefixedId($product->{self::SKU_KEY});

                if ($sync->current_external_id != null && $sync->current_external_id > intval($product->{self::PRIMARY_KEY})) {
                    $counter++;
                    continue;
                }

                Log::debug(self::SUPPLIER_NAME . "> -- downloading product", ["external_id" => (string) $product->{self::PRIMARY_KEY}, "sku" => (string) $product->{self::SKU_KEY}]);
                $this->updateSynchStatus(self::SUPPLIER_NAME, "in progress", $product->{self::PRIMARY_KEY});

                $product_family_details = $this->getSingleProductInfo($product);

                if ($sync->product_import_enabled) {
                    $this->prepareAndSaveProductData(compact("product_family_details", "prices"));
                }

                if ($sync->stock_import_enabled) {
                    $this->prepareAndSaveStockData(compact("product_family_details", "stocks"));
                }

                if ($sync->marking_import_enabled) {
                    // $this->prepareAndSaveMarkingData(compact("product", "markings"));
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
            Log::error(self::SUPPLIER_NAME . "> -- Error: " . $e->getMessage(), ["external_id" => $product->baseinfo->{self::PRIMARY_KEY}, "exception" => $e]);
            $this->updateSynchStatus(self::SUPPLIER_NAME, "error");
        }
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
    private function getSingleProductInfo(SimpleXMLElement $product): SimpleXMLElement
    {
        $style_data = Http::accept("text/xml")
            ->get((string) $product->url_style_xml, [])
            ->throwUnlessStatus(200)
            ->body();
        $style_data = (new SimpleXMLElement($style_data))->xpath("//style")[0];
        return $style_data;
    }

    private function getStockInfo(): Collection
    {
        $res = Http::accept("text/csv")
            ->get($this->getAuthenticatedUrlClients() . "webservice/R01_000/stockinfo/falkross_de.csv", [])
            ->throwUnlessStatus(200)
            ->body();
        $res = collect(explode("\r\n", $res))
            ->skip(1)
            ->filter() // remove empty lines
            ->map(fn($row) => array_combine(["sku", "quantity"], str_getcsv($row, ";")));

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

        $variants = $product->xpath("//sku_list/sku");
        $imgs = collect($product->xpath("//style_picture_list/style_picture/url"))
            ->map(fn ($img) => (string) $img);

        foreach ($variants as $i => $variant) {
            Log::debug(self::SUPPLIER_NAME . "> --- saving product variant", ["external_id" => (string) $variant->sku_artnum, "count" => ($i + 1)."/".count($variants)]);
            $this->saveProduct(
                $this->getPrefixedId($variant->sku_artnum),
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
                size_name: (string) $variant->sku_size_name,
            );
        }
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
                $stock["quantity"]
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

        foreach ($product->markgroups?->children() ?? [] as $technique) {
            $marking = $markings->firstWhere("ID", $technique->id->__toString());
            if (!$marking) continue;

            $this->saveMarking(
                $this->getPrefixedId($product->baseinfo->{self::SKU_KEY}),
                "", // no positions available
                $technique->name?->__toString(),
                $this->sanitizePrintSize($technique->marking_size?->__toString()),
                null,
                $marking["ColorsMax"] > 1
                    ? collect(range(1, $marking["ColorsMax"]))
                        ->mapWithKeys(fn ($i) => ["$i kolor" . ($i >= 5 ? "ów" : ($i == 1 ? "" : "y")) => [
                            "mod" => "*$i",
                            "include_setup" => true,
                        ]])
                        ->toArray()
                    : null,
                collect($marking["Price"])
                    ->filter(fn ($p, $label) => Str::startsWith($label, "Price From "))
                    ->mapWithKeys(fn ($p, $label) => [$label => [
                        "price" => as_number($p) + as_number($marking["Price"]["Pakowanie"]),
                    ]])
                    ->merge( // flat price defined for every quantity because packing price still has to count
                        collect(range(1, $marking["Price"]["Ryczalt quantity"]))
                            ->mapWithKeys(fn ($i) => ["Price From $i" => [
                                "price" => as_number($marking["Price"]["Ryczalt price"]) + as_number($marking["Price"]["Pakowanie"]) * $i,
                                "flat" => true,
                            ]])
                    )
                    ->mapWithKeys(fn ($p, $label) => [Str::afterLast($label, "Price From ") => $p])
                    ->sortBy(fn ($p, $label) => intval($label))
                    ->toArray(),
                as_number($marking["Price"]["Przygotowanie"])
            );
        }
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
