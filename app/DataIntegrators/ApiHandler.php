<?php

namespace App\DataIntegrators;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

abstract class ApiHandler
{
    private const URL = self::URL;
    private const SUPPLIER_NAME = self::SUPPLIER_NAME;

    abstract public function getData(string $params = null): Collection;
    abstract public function getPrefix(): string | array;

    abstract public function authenticate(): void;
    abstract public function downloadAndStoreAllProductData(): void;

    public function getDataWithPrefix(string $product_code)
    {
        preg_match("/^[A-Z]*/", $product_code, $matches);
        $prefix = $matches[0];

        $provider_prefix = (gettype($this->getPrefix()) == "array")
            ? $this->getPrefix()
            : [$this->getPrefix()];

        // abort fetch if prefix exists and itdoesn't match
        return (strlen($prefix) > 0 && !in_array($prefix, $provider_prefix))
            ? collect()
            : $this->getData($product_code);
    }

    public function saveProduct(
        string $id,
        string $name,
        string $description,
        string $product_family_id,
        array $image_urls,
        int $main_attribute_id = null
    ) {
        $product = Product::updateOrCreate(
            ["id" => $id],
            compact(
                "id",
                "name",
                "description",
                "product_family_id",
                "main_attribute_id",
            )
        );

        foreach ($image_urls as $url) {
            $contents = file_get_contents($url);
            $filename = basename($url);
            Storage::put("public/products/$product->id/$filename", $contents, [
                "visibility" => "public",
                "directory_visibility" => "public",
            ]);
        }
    }
}
