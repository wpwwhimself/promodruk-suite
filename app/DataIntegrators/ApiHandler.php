<?php

namespace App\DataIntegrators;

use App\Models\Product;
use App\Models\ProductSynchronization;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class ApiHandler
{
    private const URL = self::URL;
    private const SUPPLIER_NAME = self::SUPPLIER_NAME;

    abstract public function getPrefix(): string | array;

    abstract public function authenticate(): void;
    abstract public function downloadAndStoreAllProductData(ProductSynchronization $sync): void;

    public function saveProduct(
        string $id,
        string $name,
        string $description,
        string $product_family_id,
        array $image_urls,
        string $original_category = null,
        string $original_color_name = null
    ) {
        $product = Product::updateOrCreate(
            ["id" => $id],
            compact(
                "id",
                "name",
                "description",
                "product_family_id",
                "original_color_name",
                "original_category",
                "image_urls",
            )
        );

        // foreach ($image_urls as $url) {
        //     if (empty($url)) continue;
        //     try {
        //         $contents = file_get_contents($url);
        //         $filename = basename($url);
        //         Storage::put("public/products/$product->id/$filename", $contents, [
        //             "visibility" => "public",
        //             "directory_visibility" => "public",
        //         ]);
        //     } catch (\Exception $e) {
        //         Log::error($e->getMessage());
        //         continue;
        //     }
        // }
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
}
