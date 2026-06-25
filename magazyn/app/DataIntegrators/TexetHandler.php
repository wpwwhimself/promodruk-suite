<?php

namespace App\DataIntegrators;

use App\Models\ProductFamily;
use Illuminate\Http\Client\ConnectionException;
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

            $this->sync->addLog("in progress", 2, "Downloading product: $sku ($external_id)", $external_id);

            if ($this->canProcessModule("product")) {
                $this->prepareAndSaveProductData(compact("sku", "products"));
            }

            if ($this->canProcessModule("stock")) {
                $this->prepareAndSaveStockData(compact("external_id", "stocks"));
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
                (string) $p->{self::SKU_KEY} ?: (string) $p->kod,
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
                ->timeout(60)
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
        $prepared_sku = $this->getPrefixedId($product->{self::SKU_KEY});
        $product_name = (string) $product->nazwa;

        $variants = collect($product->xpath("detale/detal"))
        ->groupBy(fn ($var) => $var->kolor_kod);
        $imgs = collect($product->xpath("zdjecia/zdjecie"))
        ->groupBy(fn ($img) => $img->kolor_kod);

        // niektóre produkty są pojedynczymi wariantami, ale powinny być wariantowane wspólnie
        $product_name_until_comma = Str::beforeLast((string) $product->nazwa, ",");
        if (
            $product_name_until_comma != $product_name
            && !Str::contains($prepared_sku, "-")
            && $products->count(fn ($p) => Str::beforeLast((string) $p->nazwa, ",") == $product_name_until_comma) > 1
        ) {
            $product_name = $product_name_until_comma;
            $prepared_sku = ProductFamily::where("name", $product_name)->where("source", self::SUPPLIER_NAME)->first()?->id;
            if (empty($prepared_sku)) {
                do {
                    $random_number = Str::of(rand(0, 9999))->padLeft(4, "0");
                    $id = $this->getPrefixedId("ZZ".$random_number);
                } while (ProductFamily::where("id", $id)->exists());
                $prepared_sku = $id;
            }
        }

        $imported_ids = [];
        $i = 0;

        foreach ($variants as $color_code => $size_variants) {
            if (count($imgs[$color_code] ?? []) == 0) {
                // jeśli produkt nie ma zdjęć, to uznaj, że nie ma go w ofercie
                $this->sync->addLog("in progress", 3, "$prepared_sku (".($i++ + 1)."/".count($variants).") skipped (no images)", (string) $product->{self::PRIMARY_KEY});
                continue;
            }

            $variant = $size_variants->first();
            if ($variant->indeks == "Produkt wycofany z oferty") {
                $this->sync->addLog("in progress", 3, "$prepared_sku (".($i++ + 1)."/".count($variants).") skipped (pulled from offer)", (string) $product->{self::PRIMARY_KEY});
                continue;
            }

            $color_name = (string) $variant->kolor;
            if ($color_name == "default") {
                $color_name = Str::afterLast((string) $product->nazwa, ", ");
            }

            $this->sync->addLog("in progress", 3, "saving product variant ".$prepared_sku." (".($i++ + 1)."/".count($variants).")", (string) $product->{self::PRIMARY_KEY});
            $ret[] = $this->saveProduct(
                Str::beforeLast($variant->indeks, "-"),
                $product->{self::PRIMARY_KEY},
                $product_name,
                html_entity_decode(html_entity_decode((string) $product->opis ?? "")),
                $prepared_sku,
                as_number((string) $variant->cena_rekomendowana),
                collect($imgs[$color_code] ?? [])->map(fn ($img) => (string) $img->url)->sort()->toArray(),
                collect($imgs[$color_code] ?? [])->map(fn ($img) => (string) $img->url)->sort()->toArray(),
                $this->getPrefix(),
                $this->processTabs($product, $products),
                (string) $product->kategoria ?: "— bd. —",
                $color_name,
                source: self::SUPPLIER_NAME,
                sizes: $size_variants->map(fn ($s) => [
                    "size_name" => (string) $s->rozmiar,
                    "size_code" => (string) $s->rozmiar,
                    "full_sku" => $this->getPrefixedId($s->indeks),
                ])->toArray(),
                marked_as_new: ((string) $product->nowosc) == "Nowość",
            );

            $imported_ids[] = $prepared_sku;
        }

        // tally imported IDs
        $this->imported_ids = array_merge($this->imported_ids, $imported_ids);

        return $ret;
    }

    /**
     * @param array $data external_id, stocks
     */
    public function prepareAndSaveStockData(array $data): array
    {
        [
            "external_id" => $external_id,
            "stocks" => $stocks,
        ] = $data;

        $stocks_for_this_product = $stocks->filter(fn ($pr) => Str::startsWith((string) $pr->id, $external_id));
        $prefixed_id = $this->getPrefixedId($external_id);
        if (Str::endsWith($prefixed_id, "-")) {
            $prefixed_id = Str::before($prefixed_id, "-"); // obcina do pierwszego myślnika
        }

        $ret = $stocks_for_this_product
            ->map(fn ($stock) => $this->saveStock(
                $prefixed_id,
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

    private function processTabs(SimpleXMLElement $product, Collection $all_products) {
        $size_table_url = self::URL . "upload/rozmiary/" . (string) $product->{self::SKU_KEY} . ".pdf";
        $specification = null;
        try {
            if (Http::get($size_table_url)->successful()) {
                $specification = ["Tabela rozmiarów" => $size_table_url];
            }
        } catch (ConnectionException $e) {
            // timeout, więc też nie ma specyfikacji
        }

        $alternative = null;
        if ((string) $product->odpowiednik) {
            $alternative_product = $all_products->firstWhere(fn ($p) => (string) $p->{self::SKU_KEY} == (string) $product->odpowiednik);
            if ($alternative_product) {
                $target_gender = Str::of($alternative_product->model)
                    ->substr(0, -1)
                    ->lower();

                $alternative = ["Zobacz odpowiednik $target_gender: $alternative_product->nazwa" => "/produkty/szukaj?query=" . $this->getPrefixedId($product->odpowiednik)];
            }
        }

        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return array_filter([
            !$alternative ? null : [
                "name" => "Odpowiednik (damski/męski)",
                "cells" => [["type" => "tiles", "content" => $alternative, "icons" => "arrow-right"]]
            ],
            !$specification ? null : [
                "name" => "Tabele rozmiarów",
                "cells" => [["type" => "tiles", "content" => $specification]],
            ],
        ]);
    }
    #endregion
}
