<?php

namespace Tests\Browser;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductTest extends DuskTestCase
{
    public function testShouldSeeStandardProductPage(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::all()
                ->filter(fn ($p) => !$p->is_custom && $p->price && collect($p->family_variants_list)->count() > 1)
                ->random();

            $browser->visitRoute("product", ["id" => $product->id])
                ->assertSee($product->name)
                ->assertSee($product->id)
                ->assertSee(asPln($product->price))
                // variant selectors
                ->assertSee("wybierz, aby zobaczyć zdjęcia i stan magazynowy")
                ->assertVisible("div.color-tile")
                ->mouseover("div.color-tile")
                ->waitFor(".tippy-popper")
                ->assertSeeIn(".tippy-popper", "srebrny /")
                // tabs
                ->assertVisible(".tabs .content-box")
            ;
        });
    }

    public function testShouldSeeCustomProductPage(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::all()
                ->filter(fn ($p) => $p->is_custom && collect($p->family_variants_list)->count() > 1)
                ->random();

            $browser->visitRoute("product", ["id" => $product->id])
                ->assertSee($product->name)
                ->assertSee($product->id)
                // ->assertSee(asPln($product->price))
                // variant selectors
                ->assertSee("wybierz, aby zobaczyć zdjęcia")
                ->assertVisible("div.color-tile")
                ->mouseover("div.color-tile")
                ->waitFor(".tippy-popper")
                ->assertSeeIn(".tippy-popper", "czerwony")
                ->assertDontSee("stan magazynowy")
                ->assertDontSee("szt.")
                // tabs
                ->assertVisible(".tabs .content-box")
            ;
        });
    }

    public function testShouldSeeDistinctFamilyNameOnProductTile(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::whereRaw("name <> family_name")
                ->where("family_name", "<>", "")
                ->get()
                ->random();

            // check listing
            $browser->visitRoute("category-".$product->categories->first()->id)
                ->assertSee(Str::limit($product->family_name, 40))
                ->assertDontSee(Str::limit($product->name));

            // check editor
            $browser->loginAs(1)
                ->visitRoute("products-edit", ["id" => $product->family_prefixed_id])
                ->assertSee($product->family_name);
        });
    }
}
