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
                ->assertVisible("div.variant-tile")
                ->mouseover("div.variant-tile")
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
                ->assertVisible("div.variant-tile")
                ->mouseover("div.variant-tile")
                ->assertSeeIn(".tippy-popper", "czerwony")
                ->assertDontSee("stan magazynowy")
                ->assertDontSee("szt.")
                // tabs
                ->assertVisible(".tabs .content-box")
            ;
        });
    }
}
