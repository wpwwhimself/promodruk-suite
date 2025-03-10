<?php

namespace Tests\Feature;

use App\DataIntegrators\AndaHandler;
use App\DataIntegrators\AsgardHandler;
use App\DataIntegrators\AxpolHandler;
use App\DataIntegrators\EasygiftsHandler;
use App\DataIntegrators\FalkRossHandler;
use App\DataIntegrators\MacmaHandler;
use App\DataIntegrators\MaximHandler;
use App\DataIntegrators\MidoceanHandler;
use App\DataIntegrators\PARHandler;
use App\Models\Product;
use App\Models\ProductSynchronization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataIntegratorTest extends TestCase
{
    use DatabaseTransactions;

    public function testAsgardDataIsComplete()
    {
        $handler = new AsgardHandler(ProductSynchronization::find("Asgard"));
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
        $handler = new MidoceanHandler(ProductSynchronization::find("Midocean"));
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

    public function testEasygiftsDataIsComplete()
    {
        $handler = new EasygiftsHandler(ProductSynchronization::find("Easygifts"));
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "399103",
            "name" => "Długopis metalowy półżelowy soft touch DUNMORE",
            "description_length" => 100,
            "color_name" => "czarny",
        ];
        $original_sku = $testProduct["original_sku"];
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere(fn ($p) => $p->baseinfo->{$handler::SKU_KEY} == $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "prices"));
        $handler->prepareAndSaveStockData(compact("product", "stocks"));
        $handler->prepareAndSaveMarkingData(compact("product", "markings"));

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
            // $this->assertNotEmpty($model->position); // no positions available
            $this->assertNotEmpty($model->technique);
            $this->assertNotEmpty($model->print_size);
            // $this->assertNotEmpty($model->images); // no images
            $this->assertNotEmpty($model->quantity_prices);
    }

    public function testPARDataIsComplete()
    {
        $handler = new PARHandler(ProductSynchronization::find("PAR"));
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "R73375.08",
            "name" => "Długopis Lind, czerwony",
            "description_length" => 40,
            "color_name" => "czerwony",
        ];
        $original_sku = $testProduct["original_sku"];
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product"));
        $handler->prepareAndSaveStockData(compact("product", "stocks"));
        $handler->prepareAndSaveMarkingData(compact("product", "markings"));

        // check if all data is there
        $model = Product::find($product_id);

        $this->assertModelExists($model);
            $this->assertStringStartsWith($model->name, $testProduct["name"]);
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
            // $this->assertNotEmpty($model->images); // no images available
            $this->assertNotEmpty($model->quantity_prices);
    }

    public function testMacmaDataIsComplete()
    {
        $handler = new MacmaHandler(ProductSynchronization::find("Macma"));
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "markings" => $markings,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "1399103",
            "name" => "Długopis aluminiowy półżelowy soft touch",
            "description_length" => 100,
            "color_name" => "czarny",
        ];
        $original_sku = $testProduct["original_sku"];
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere(fn ($p) => $p->baseinfo->{$handler::SKU_KEY} == $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "prices"));
        $handler->prepareAndSaveStockData(compact("product", "stocks"));
        $handler->prepareAndSaveMarkingData(compact("product", "markings"));

        // check if all data is there
        $model = Product::find($product_id);

        $this->assertModelExists($model);
            $this->assertStringStartsWith($model->name, $testProduct["name"]);
            $this->assertGreaterThan($testProduct["description_length"], strlen($model->description));
            $this->assertEquals($model->original_color_name, $testProduct["color_name"]);
            $this->assertNotEmpty($model->image_urls);
            $this->assertNotEmpty($model->thumbnails);
            $this->assertGreaterThan(0, $model->price);
            $this->assertNotEmpty($model->tabs);

        $this->assertModelExists($model->stock);

        $model = $model->markings->first();
        $this->assertModelExists($model);
            // $this->assertNotEmpty($model->position); // no positions available
            $this->assertNotEmpty($model->technique);
            $this->assertNotEmpty($model->print_size);
            // $this->assertNotEmpty($model->images); // no images available
            $this->assertNotEmpty($model->quantity_prices);
    }

    public function testAxpolDataIsComplete()
    {
        $handler = new AxpolHandler(ProductSynchronization::find("Axpol"));
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "markings" => $markings,
            "prices" => $prices,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "V0051-02",
            "name" => "Długopis | Nathaniel",
            "description_length" => 120,
            "color_name" => "biały",
        ];
        $original_sku = $testProduct["original_sku"];
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "markings", "prices"));
        $handler->prepareAndSaveStockData(compact("product"));
        $handler->prepareAndSaveMarkingData(compact("product", "markings", "prices"));

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
            // $this->assertNotEmpty($model->images); // no images available
            $this->assertNotEmpty($model->quantity_prices);
    }

    public function testAndaDataIsComplete()
    {
        $handler = new AndaHandler(ProductSynchronization::find("Anda"));
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "prices" => $prices,
            "stocks" => $stocks,
            "labelings" => $labelings,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "AP809618-10",
            "name" => "Miraboo",
            "description_length" => 100,
            "color_name" => "czarny",
        ];
        $original_sku = $testProduct["original_sku"];
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "prices", "labelings"));
        $handler->prepareAndSaveStockData(compact("product", "stocks"));

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
            $this->assertNotEmpty($model->images); // no images available
            $this->assertNotEmpty($model->quantity_prices);
    }

    public function testMaximDataIsComplete()
    {
        $handler = new MaximHandler(ProductSynchronization::find("Maxim"));
        $handler->authenticate();

        // pull data
        [
            "products" => $products,
            "stocks" => $stocks,
            "params" => $params,
        ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        $testProduct = [
            "original_sku" => "C227-68583",
            "name" => "Manhattan Set",
            "description_length" => 150,
            "color_name" => "biały",
        ];
        $original_sku = $testProduct["original_sku"];
        $family_sku = Str::before($original_sku, "-");
        $product_id = $handler->getPrefixedId($original_sku);
        $product = $products->firstWhere($handler::SKU_KEY, $family_sku);
        $variant = collect($product["Warianty"] ?? $product["Variants"])->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        $handler->prepareAndSaveProductData(compact("product", "variant", "params"));
        $handler->prepareAndSaveStockData(compact("variant", "stocks"));

        // check if all data is there
        $model = Product::find($product_id);

        $this->assertModelExists($model);
            $this->assertEquals($model->name, $testProduct["name"]);
            $this->assertGreaterThan($testProduct["description_length"], strlen($model->description));
            $this->assertEquals($model->original_color_name, $testProduct["color_name"]);
            $this->assertNotEmpty($model->image_urls);
            $this->assertNotEmpty($model->thumbnails);
            $this->assertNull($model->price); // usunięte ceny
            $this->assertNotEmpty($model->tabs);

        $this->assertModelExists($model->stock);
        $this->assertDatabaseMissing("product_markings", ["product_id" => $product_id]);
    }

    public function testFalkRossDataIsComplete()
    {
        $handler = new FalkRossHandler(ProductSynchronization::find("FalkRoss"));
        $handler->authenticate();

        // pull data
        // [
        //     "style_list" => $style_list,
        //     "prices" => $prices,
        //     "stocks" => $stocks,
        //     "markings" => $markings,
        // ] = $handler->downloadData(true, true, true);

        // pick certain specimen
        // $testProduct = [
        //     "original_sku" => "V0051-02",
        //     "name" => "Długopis | Nathaniel",
        //     "description_length" => 120,
        //     "color_name" => "biały",
        // ];
        // $original_sku = $testProduct["original_sku"];
        // $product_id = $handler->getPrefixedId($original_sku);
        // $product = $products->firstWhere($handler::SKU_KEY, $original_sku);

        // try to save it
        // $handler->prepareAndSaveProductData(compact("product", "markings", "prices"));
        // $handler->prepareAndSaveStockData(compact("product"));
        // $handler->prepareAndSaveMarkingData(compact("product", "markings", "prices"));

        // check if all data is there
        // $model = Product::find($product_id);

        // $this->assertModelExists($model);
        //     $this->assertEquals($model->name, $testProduct["name"]);
        //     $this->assertGreaterThan($testProduct["description_length"], strlen($model->description));
        //     $this->assertEquals($model->original_color_name, $testProduct["color_name"]);
        //     $this->assertNotEmpty($model->image_urls);
        //     $this->assertNotEmpty($model->thumbnails);
        //     $this->assertGreaterThan(0, $model->price);
        //     $this->assertNotEmpty($model->tabs);

        // $this->assertModelExists($model->stock);

        // $model = $model->markings->first();
        // $this->assertModelExists($model);
        //     $this->assertNotEmpty($model->position);
        //     $this->assertNotEmpty($model->technique);
        //     $this->assertNotEmpty($model->print_size);
        //     // $this->assertNotEmpty($model->images); // no images available
        //     $this->assertNotEmpty($model->quantity_prices);
    }
}
