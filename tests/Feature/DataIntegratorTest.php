<?php

namespace Tests\Feature;

use App\DataIntegrators\AsgardHandler;
use App\DataIntegrators\MidoceanHandler;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataIntegratorTest extends TestCase
{
    use DatabaseTransactions;

    public function testAsgardDataIsComplete()
    {
        $handler = new AsgardHandler();
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "categories" => $categories,
            "subcategories" => $subcategories,
            "marking_labels" => $marking_labels,
            "marking_prices" => $marking_prices,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "19061-03",
            "name" => "Długopis KALIPSO",
            "description_length" => 100,
            "color_name" => "niebieski",
        ];
        $original_sku = $testProduct["original_sku"];
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "categories", "subcategories"));
        $handler->prepareAndSaveStockData(compact("product"));
        $handler->prepareAndSaveMarkingData(compact("product", "marking_labels", "marking_prices"));

        // check if all data is there
        $model = Product::find($product_id);

        $this->assertModelExists($model);
            $this->assertEquals($model->name, $testProduct["name"]);
            $this->assertGreaterThan($testProduct["description_length"], strlen($model->description));
            $this->assertEquals($model->original_color_name, $testProduct["color_name"]);
            $this->assertNotEmpty($model->image_urls);
            $this->assertNotEmpty($model->thumbnails);
            $this->assertGreaterThan(0, $model->price);
            $this->assertNotEmpty($model->tabs);

        $this->assertModelExists($model->stock);

        $model = $model->markings->first();
        $this->assertModelExists($model);
            $this->assertNotEmpty($model->position);
            $this->assertNotEmpty($model->technique);
            $this->assertNotEmpty($model->print_size);
            $this->assertNotEmpty($model->images);
            $this->assertNotEmpty($model->quantity_prices);
    }

    public function testMidoceanDataIsComplete()
    {
        $handler = new MidoceanHandler();
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "marking_labels" => $marking_labels,
            "markings" => $markings,
            "marking_prices" => $marking_prices,
            "marking_manipulations" => $marking_manipulations,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "MO3314-12",
            "name" => "Długopis Rio kolor",
            "description_length" => 50,
            "color_name" => "Granatowy",
        ];
        $original_sku = $testProduct["original_sku"];
        $family_sku = Str::before($original_sku, "-");
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere("master_code", $family_sku);
        $variant = collect($product["variants"])->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "variant", "prices"));
        $handler->prepareAndSaveStockData(compact("variant", "stocks"));
        $handler->prepareAndSaveMarkingData(compact("product", "variant", "markings", "marking_manipulations", "marking_labels", "marking_prices"));

        // check if all data is there
        $model = Product::find($product_id);

        $this->assertModelExists($model);
            $this->assertEquals($model->name, $testProduct["name"]);
            $this->assertGreaterThan($testProduct["description_length"], strlen($model->description));
            $this->assertEquals($model->original_color_name, $testProduct["color_name"]);
            $this->assertNotEmpty($model->image_urls);
            $this->assertNotEmpty($model->thumbnails);
            $this->assertGreaterThan(0, $model->price);
            $this->assertNotEmpty($model->tabs);

        $this->assertModelExists($model->stock);

        $model = $model->markings->first();
        $this->assertModelExists($model);
            $this->assertNotEmpty($model->position);
            $this->assertNotEmpty($model->technique);
            $this->assertNotEmpty($model->print_size);
            $this->assertNotEmpty($model->images);
            $this->assertNotEmpty($model->quantity_prices);
    }
}
