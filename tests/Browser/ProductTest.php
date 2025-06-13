<?php

namespace Tests\Browser;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductTest extends DuskTestCase
{
    public function testShouldSeeStandardProductPage(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::find("AS19061-00");

            $browser->visitRoute("product", ["id" => $product->id])
                ->assertSee($product->name)
                ->assertSee($product->id)
                ->assertSee(asPln($product->price))
                // variant selectors
                ->assertSee("Wybierz kolor, aby zobaczyć zdjęcia i stan magazynowy")
                ->assertVisible("div.color-tag")
                ->mouseover("div.color-tag")
                ->assertVisible(".tippy-popper")
                ->assertSeeIn(".tippy-popper", "srebrny /")
                // tabs
                ->assertVisible(".tabs .content-box")
            ;
        });
    }

    public function testShouldSeeCustomProductPage(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::find("ZR001-01");

            $browser->visitRoute("product", ["id" => $product->id])
                ->assertSee($product->name)
                ->assertSee($product->id)
                ->assertSee(asPln($product->price))
                // variant selectors
                ->assertSee("Wybierz kolor, aby zobaczyć zdjęcia")
                ->assertVisible("div.color-tag")
                ->mouseover("div.color-tag")
                ->assertSeeIn(".tippy-popper", "czerwony")
                ->assertDontSee("stan magazynowy")
                ->assertDontSee("szt.")
                // tabs
                ->assertVisible(".tabs .content-box")
            ;
        });
    }

    public function test_should_see_distinct_family_name_on_product_tile(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::whereRaw("name <> family_name")
                ->where("family_name", "<>", "")
                ->get()
                ->random();

            // check listing
            $browser->visitRoute("category-".$product->categories->first()->id)
                ->assertSee($product->family_name)
                ->assertDontSee($product->name);

            // check editor
            $browser->loginAs(1)
                ->visitRoute("products-edit", ["id" => $product->family_prefixed_id])
                ->assertSee($product->family_name);
        });
    }
}
