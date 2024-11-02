<?php

namespace Tests\Feature;

use App\DataIntegrators\AsgardHandler;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
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
        $original_sku = "19061-03";
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "categories", "subcategories"));
        $handler->prepareAndSaveStockData(compact("product"));
        $handler->prepareAndSaveMarkingData(compact("product", "marking_labels", "marking_prices"));

        // check if all data is there
        $model = Product::find($product_id);

        $this->assertModelExists($model);
            $this->assertEquals($model->name, "Długopis KALIPSO");
            $this->assertGreaterThan(100, strlen($model->description));
            $this->assertEquals($model->original_color_name, "niebieski");
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
