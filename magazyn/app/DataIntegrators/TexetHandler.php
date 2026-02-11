<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class TexetHandler extends ApiHandler
{
    #region constants
    private const URL = "https://www.texet.pl/";
    private const SUPPLIER_NAME = "Texet";
    public function getPrefix(): string { return "TE"; }
    private const PRIMARY_KEY = "id";
    public const SKU_KEY = "nr_katalogowy";
    public function getPrefixedId(string $original_sku): string { return $this->getPrefix() . $original_sku; }
    public const BRANDS = [
        27 => [
            "name" => "ByOn",
            "key" => "a1e73094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        8 => [
            "name" => "Clique",
            "key" => "0057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121822",
        ],
        17 => [
            "name" => "Cottover",
            "key" => "91e73094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        28 => [
            "name" => "Cutter&Buck",
            "key" => "a1173094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        3 => [
            "name" => "D.A.D",
            "key" => "b057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121822",
        ],
        19 => [
            "name" => "Derby of Sweden",
            "key" => "91073094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        2 => [
            "name" => "James Harvest",
            "key" => "a057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121822",
        ],
        10 => [
            "name" => "J.Harvest & Frost",
            "key" => "91973094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        6 => [
            "name" => "Lord Nelson(D&J)",
            "key" => "e057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121b22",
        ],
        1 => [
            "name" => "Printer",
            "key" => "9057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121822",
        ],
        12 => [
            "name" => "Printer Essentials",
            "key" => "91b73094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        18 => [
            "name" => "Printer Red",
            "key" => "91173094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        9 => [
            "name" => "Projob",
            "key" => "1057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121822",
        ],
        5 => [
            "name" => "Sagaform",
            "key" => "d057d5e4c5f531f4a407c1c1e1f1f6c1c13184e30574d314808121b22",
        ],
        24 => [
            "name" => "Sagaform bags First",
            "key" => "a1d73094a4944327c4d3a04101c1e69180816032f53503749440a136d56",
        ],
        29 => [
            "name" => "Tenson",
            "key" => "a1073094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        30 => [
            "name" => "Untagged Movement",
            "key" => "b1973094a4944327c4d3a04101c1e69180816032f53503749440a136e56",
        ],
        25 => [
            "name" => "Vakinme",
            "key" => "a1c73094a4944327c4d3a04101c1e69180816032f53503749440a136d56",
        ],
        26 => [
            "name" => "Victorian",
            "key" => "a1f73094a4944327c4d3a04101c1e69180816032f53503749440a136d56",
        ],
    ];

    private array $imported_ids = [];
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
                $this->prepareAndSaveProductData(compact("sku", "products"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("sku", "stocks"));
            }

            // if ($this->canProcessModule("marking")) {
            //     $this->prepareAndSaveMarkingData(compact("sku", "products", "markings"));
            // }

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

        $products = ($product) ? $this->getProductInfo() : collect();
        $stocks = ($stock) ? $this->getStockInfo() : collect();

        $ids = collect([ $products, $stocks ])
            ->firstWhere(fn ($d) => $d->count() > 0)
            ->map(fn ($p) => [
                (string) $p->{self::SKU_KEY},
                (string) $p->{self::PRIMARY_KEY},
            ]);

        return compact(
            "ids",
            "products",
            "stocks",
        );
    }

    private function getStockInfo(): Collection
    {
        $stocks = Http::accept("text/xml")
            ->get(self::URL . "stany.xml", [])
            ->throwUnlessStatus(200)
            ->body();
        $stocks = new SimpleXMLElement($stocks);
        $stocks = collect($stocks->xpath("//produkt"))
            ->sort(fn ($a, $b) => (string) $a->{self::PRIMARY_KEY} <=> (string) $b->{self::PRIMARY_KEY});

        return $stocks;
    }

    private function getProductInfo(): Collection
    {
        $this->sync->addLog("pending (info)", 2, "Pulling products data. This may take a while...");
        $all_products = collect();

        foreach (self::BRANDS as $brand_id => $brand) {
            $this->sync->addLog("pending (step)", 3, $brand["name"]);
            $data = Http::accept("text/xml")
                ->get(self::URL . "pl/Cenniki/generuj-xml/firma/37470/api_key/" . env("TEXET_API_KEY_FRONT") . $brand["key"] . "/marka/$brand_id", [])
                ->throwUnlessStatus(200)
                ->body();
            $data = new SimpleXMLElement($data);
            $products = collect($data->xpath("//produkt"))
                ->filter(fn ($p) => collect($p->xpath("detale/detal"))
                    ->filter(fn ($detal) => $detal->indeks != "Produkt wycofany z oferty")
                    ->count() > 0
                );

            $this->sync->addLog("pending (step)", 4, $products->count());
            $all_products = $all_products->merge($products);
        }
        $all_products = $all_products->sort(fn ($a, $b) => (string) $a->{self::PRIMARY_KEY} <=> (string) $b->{self::PRIMARY_KEY});

        return $all_products;
    }
    #endregion

    #region processing
    /**
     * @param array $data sku, products
     */
    public function prepareAndSaveProductData(array $data): array
    {
        [
            "sku" => $sku,
            "products" => $products,
        ] = $data;

        $product = $products->firstWhere(fn ($p) => (string) $p->{self::SKU_KEY} == $sku);

        $variants = collect($product->xpath("detale/detal"))
            ->groupBy(fn ($var) => $var->kolor_kod);
        $imgs = collect($product->xpath("zdjecia/zdjecie"))
            ->groupBy(fn ($img) => $img->kolor_kod);

        $imported_ids = [];
        $i = 0;

        foreach ($variants as $color_code => $size_variants) {
            $variant = $size_variants->first();
            $prepared_sku = $product->{self::SKU_KEY}; //todo poprawić?

            $this->sync->addLog("in progress", 3, "saving product variant ".$prepared_sku."(".($i++ + 1)."/".count($variants).")", (string) $product->{self::PRIMARY_KEY});
            $ret[] = $this->saveProduct(
                $variant->indeks,
                $variant->id,
                (string) $product->nazwa,
                html_entity_decode((string) $product->opis ?? ""),
                $this->getPrefixedId($product->{self::SKU_KEY}),
                as_number((string) $variant->cena),
                collect($imgs[$color_code] ?? [])->map(fn ($img) => (string) $img->url)->sort()->toArray(),
                collect($imgs[$color_code] ?? [])->map(fn ($img) => (string) $img->url)->sort()->toArray(),
                $this->getPrefix(),
                null, // $this->processTabs($product),
                (string) $product->kategoria,
                (string) $variant->kolor,
                source: self::SUPPLIER_NAME,
                marked_as_new: ((string) $product->nowosc) == "Nowość",
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
    public function prepareAndSaveStockData(array $data): array
    {
        [
            "sku" => $sku,
            "stocks" => $stocks,
        ] = $data;

        $ret = $stocks->filter(fn ($pr) => Str::startsWith((string) $pr->kod, $sku))
            ->map(fn ($stock) => $this->saveStock(
                $this->getPrefixedId($stock->kod),
                (int) $stock->ilosc,
                null,
                null
            ))
            ->toArray();

        return $ret;
    }

    /**
     * @param array $data ...
     */
    public function prepareAndSaveMarkingData(array $data): ?array
    {
        // disabled

        return null;
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
