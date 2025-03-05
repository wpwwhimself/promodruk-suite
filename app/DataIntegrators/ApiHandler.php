<?php

namespace App\DataIntegrators;

use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductMarking;
use App\Models\ProductSynchronization;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

abstract class ApiHandler
{
    private const URL = self::URL;
    private const SUPPLIER_NAME = self::SUPPLIER_NAME;
    private const PRIMARY_KEY = self::PRIMARY_KEY;
    public const SKU_KEY = self::SKU_KEY;

    abstract public function getPrefix(): string | array;
    abstract public function getPrefixedId(string $original_sku): string;

    abstract public function authenticate(): void;
    abstract public function downloadData(bool $product, bool $stock, bool $marking): array;
    abstract public function downloadAndStoreAllProductData(): void;

    abstract public function prepareAndSaveProductData(array $data): void;
    abstract public function prepareAndSaveStockData(array $data): void;
    abstract public function prepareAndSaveMarkingData(array $data): void;

    public function __construct(
        public ProductSynchronization $sync,
    ) {
        $this->sync = $sync;
    }

    #region helpers
    protected function deleteUnsyncedProducts(array $product_ids): void
    {
        $product_ids = array_map(fn ($id) => Str::padLeft($id, 15, "0"), $product_ids);

        $unsynced_products = Product::whereHas("productFamily", fn ($q) => $q->where("source", $this->sync->supplier_name))
            ->where(fn ($q) => $q
                ->whereBetween("import_id", [(string) min($product_ids), (string) max($product_ids)])
                ->whereNotIn("import_id", $product_ids)
                ->orWhereNull("import_id")
            );
        $this->sync->addLog("pending (info)", 2, "Clearing unsynced products found: " . $unsynced_products->count());

        $unsynced_products->delete();
    }

    protected function mapXml($callback, ?SimpleXMLElement $xml): array
    {
        $ret = [];
        foreach ($xml?->children() ?? [] as $el) {
            $ret[] = $callback($el);
        }
        return $ret;
    }

    /**
     * assume size is mentioned in cm and make it in mm
     */
    protected function sanitizePrintSize(string $size): string
    {
        if (Str::contains($size, "mm")) return $size;

        $size = Str::replace("cm", "", $size);
        $size = Str::replace("X", "x", $size);
        $size = trim($size);
        $size = collect(explode("x", $size))
            ->map(fn ($s) => as_number($s) * 10)
            ->join("x");
        $size .= " mm";

        return $size;
    }
    #endregion

    #region synchronization status changes
    protected function reportSynchCount(int $counter, int $total): void
    {
        $this->sync->addLog("in progress", 2, "Synced: $counter / $total");
    }
    #endregion

    #region products processing
    /**
     * @param array $image_urls can be passed either as [image_urls] or [[variant_image_urls], [product_image_urls]]
     * @param array $thumbnail_urls see above
     */
    public function saveProduct(
        string $original_sku,
        string $import_id,
        string $name,
        ?string $description,
        string $product_family_id,
        ?float $price,
        array $image_urls,
        array $thumbnail_urls,
        string $prefix,
        ?array $tabs = null,
        ?string $original_category = null,
        ?string $original_color_name = null,
        bool $downloadPhotos = false,
        ?string $source = null,
        float $manipulation_cost = 0,
        bool $enable_discount = true,
        ?array $sizes = null,
    ) {
        //* colors processing *//
        // color replacements -- match => replacement
        $color_replacements = [
            "butelkowy" => "zielony",
            "fuksji" => "fuksja",
            "pomarańcz" => "pomarańczowy",

            "black" => "czarny",
            "brown" => "brązowy",
            "blue" => "niebieski",
            "green" => "zielony",
            "orange" => "pomarańczowy",
            "purple" => "fioletowy",
            "red" => "czerwony",
            "white" => "biały",
            "yellow" => "żółty",
        ];

        foreach (preg_split("/[\s\/\(\)]+/", Str::lower($original_color_name)) as $color_part) {
            if (!isset($color_replacements[$color_part])) continue;
            $original_color_name = Str::replace($color_part, $color_replacements[$color_part], $original_color_name);
        }

        $prefixed_id = Str::startsWith($original_sku, $prefix)
            ? $original_sku
            : $prefix . $original_sku;
        $prefixed_product_family_id = Str::startsWith($product_family_id, $prefix)
            ? $product_family_id
            : $prefix . $product_family_id;
        $import_id = Str::padLeft($import_id, 15, "0");

        // split image data between family and variant, if needed
        $product_image_urls = null;
        $variant_image_urls = null;
        $product_thumbnail_urls = null;
        $variant_thumbnail_urls = null;

        if ($image_urls && is_array(current($image_urls))) {
            // images passed as array of arrays, splitting
            [$variant_image_urls, $product_image_urls] = $image_urls;
            [$variant_thumbnail_urls, $product_thumbnail_urls] = $thumbnail_urls;
        } else {
            // all images are together, passed to variants
            $variant_image_urls = $image_urls;
            $variant_thumbnail_urls = $thumbnail_urls;
        }

        //* saving product info *//
        ProductFamily::updateOrCreate(
            ["id" => $prefixed_product_family_id],
            array_merge(
                compact(
                    "name",
                    "original_category",
                    "source",
                ),
                [
                    "id" => $prefixed_product_family_id,
                    "original_sku" => $product_family_id,
                    "description" => null,
                    "tabs" => null,
                    "image_urls" => !$downloadPhotos ? $product_image_urls : null,
                    "thumbnail_urls" => !$downloadPhotos ? $product_thumbnail_urls : null,
                ]
            )
        );

        $product = Product::updateOrCreate(
            ["id" => $prefixed_id],
            array_merge(
                compact(
                    "name",
                    "import_id",
                    "description",
                    "original_color_name",
                    "sizes",
                    "price",
                    "tabs",
                    "manipulation_cost",
                    "enable_discount",
                ),
                [
                    "id" => $prefixed_id,
                    "original_sku" => $original_sku,
                    "product_family_id" => $prefixed_product_family_id,
                    "image_urls" => !$downloadPhotos ? $variant_image_urls : null,
                    "thumbnail_urls" => !$downloadPhotos ? $variant_thumbnail_urls : null,
                ]
            )
        );

        if ($downloadPhotos) {
            foreach ([
                "product" => [$prefixed_product_family_id, $product_image_urls, $product_thumbnail_urls],
                "variant" => [$prefixed_id, $variant_image_urls, $variant_thumbnail_urls],
            ] as $product_or_variant => [$img_id, $img_image_urls, $img_thumbnail_urls]) {
                foreach ([
                    "images" => $img_image_urls,
                    "thumbnails" => $img_thumbnail_urls,
                ] as $type => $urls) {
                    Storage::deleteDirectory("public/products/$img_id/$type");

                    foreach ($urls as $url) {
                        if (empty($url)) continue;
                        try {
                            $contents = file_get_contents($url);
                            $filename = basename($url);
                            Storage::put("public/products/$img_id/$type/$filename", $contents, [
                                "visibility" => "public",
                                "directory_visibility" => "public",
                            ]);
                        } catch (\Exception $e) {
                            $this->sync->addLog("error", 2, "DATABASE ERROR: " . $e->getMessage());
                            continue;
                        }
                    }
                }
            }
        }

        if (!MainAttribute::where("name", "like", "%$original_color_name%")->exists()) {
            MainAttribute::create([
                "name" => $original_color_name,
                "color" => ""
            ]);
        }
    }

    public function saveStock(
        string $id,
        int $current_stock,
        ?int $future_delivery_amount = null,
        ?Carbon $future_delivery_date = null,
    ) {
        Stock::updateOrCreate(
            ["id" => $id],
            compact(
                "id",
                "current_stock",
                "future_delivery_amount",
                "future_delivery_date",
            )
        );
    }

    public function saveMarking(
        string $product_id,
        string $position,
        string $technique,
        ?string $print_size,
        ?array $images,
        ?array $main_price_modifiers,
        ?array $quantity_prices,
        ?float $setup_price,
        bool $enable_discount = true,
    ) {
        ProductMarking::updateOrCreate(
            [
                "product_id" => $product_id,
                "position" => $position,
                "technique" => $technique,
            ], [
                "print_size" => $print_size,
                "images" => $images,
                "main_price_modifiers" => $main_price_modifiers,
                "quantity_prices" => $quantity_prices,
                "setup_price" => $setup_price,
                "enable_discount" => $enable_discount,
            ]
        );
    }
    #endregion
}
