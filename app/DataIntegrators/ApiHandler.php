<?php

namespace App\DataIntegrators;

use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductMarking;
use App\Models\ProductSynchronization;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class ApiHandler
{
    private const URL = self::URL;
    private const SUPPLIER_NAME = self::SUPPLIER_NAME;
    private const PRIMARY_KEY = self::PRIMARY_KEY;
    private const SKU_KEY = self::SKU_KEY;

    abstract public function getPrefix(): string | array;

    abstract public function authenticate(): void;
    abstract public function downloadAndStoreAllProductData(ProductSynchronization $sync): void;

    // ? // ? // synchronization status changes // ? // ? //

    protected function updateSynchStatus(string $supplier_name, string $status, string $extra_info = null): void
    {
        $dict = [
            "pending" => 0,
            "in progress" => 1,
            "in progress (step)" => 1,
            "error" => 2,
            "complete" => 3,
        ];
        $new_status = ["synch_status" => $dict[$status]];

        switch ($status) {
            case "pending":
                $new_status["last_sync_started_at"] = Carbon::now();
                break;
            case "in progress":
                $new_status["current_external_id"] = $extra_info;
                break;
            case "in progress (step)":
                $new_status["progress"] = $extra_info;
                break;
            case "error":
                break;
            case "complete":
                $new_status["current_external_id"] = null;
                break;
        }

        ProductSynchronization::where("supplier_name", $supplier_name)
            ->update($new_status);
    }

    // ? // ? // products processing // ? // ? //

    public function saveProduct(
        string $id,
        string $name,
        ?string $description,
        string $product_family_id,
        ?float $price,
        array $image_urls,
        array $thumbnail_urls,
        string $original_sku,
        array $tabs = null,
        string $original_category = null,
        string $original_color_name = null,
        bool $downloadPhotos = false,
        string $source = null,
        float $manipulation_cost = 0,
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

        //* saving product info *//
        $product = Product::updateOrCreate(
            ["id" => $id],
            array_merge(
                compact(
                    "id",
                    "name",
                    "description",
                    "product_family_id",
                    "original_sku",
                    "original_color_name",
                    "original_category",
                    "price",
                    "tabs",
                    "source",
                    "manipulation_cost",
                ),
                [
                    "image_urls" => !$downloadPhotos ? $image_urls : null,
                    "thumbnail_urls" => !$downloadPhotos ? $thumbnail_urls : null,
                ]
            )
        );

        if ($downloadPhotos) {
            foreach ([
                "images" => $image_urls,
                "thumbnails" => $thumbnail_urls,
            ] as $type => $urls) {
                Storage::deleteDirectory("public/products/$product->id/$type");

                foreach ($urls as $url) {
                    if (empty($url)) continue;
                    try {
                        $contents = file_get_contents($url);
                        $filename = basename($url);
                        Storage::put("public/products/$product->id/$type/$filename", $contents, [
                            "visibility" => "public",
                            "directory_visibility" => "public",
                        ]);
                    } catch (\Exception $e) {
                        Log::error("> -- Error: " . $e->getMessage());
                        continue;
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
        int $future_delivery_amount = null,
        Carbon $future_delivery_date = null,
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
        float $setup_price = 0,
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
            ]
        );
    }
}
