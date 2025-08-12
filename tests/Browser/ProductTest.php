<?php

namespace Tests\Browser;

use App\Models\Product;
use App\Models\User;
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
                ->assertVisible("div.variant-tile")
                ->mouseover("div.variant-tile")
                ->assertVisible(".tippy-popper")
                ->waitFor(".tippy-popper")
                ->assertSeeIn(".tippy-popper", "srebrny /")
                // tabs
                ->assertSeeAnythingIn(".tabs")
            ;
        });
    }

    public function testShouldSeeCustomProductPage(): void
    {
        $this->browse(function (Browser $browser) {
            $product = Product::all()
                ->filter(fn ($p) => $p->is_custom && collect($p->family_variants_list)->count() > 1)
                ->random();

            $browser->visitRoute("product", ["id" => $product->front_id])
                ->assertSee($product->name)
                ->assertSee($product->id)
                // ->assertSee(asPln($product->price))
                // variant selectors
                ->assertSee("wybierz, aby zobaczyć zdjęcia")
                ->assertVisible("div.variant-tile")
                ->mouseover("div.variant-tile")
                ->waitFor(".tippy-popper")
                ->assertSeeIn(".tippy-popper", "czerwony")
                ->assertDontSee("stan magazynowy")
                ->assertDontSee("szt.")
            ;

            if ($product->has_no_unique_images) {
                // treat as one variant
                $browser->assertSee($product->family_prefixed_id);
                $browser->assertSee("Dostępne");
            } else {
                // every variant is accessible
                $browser->assertSee("wybierz, aby zobaczyć zdjęcia");
                $browser->assertSee($product->front_id);
            }

            if ($product->price) {
                $browser->assertSee(asPln($product->price));
            }
            if ($product->tabs) {
                $browser->assertSeeAnythingIn(".tabs");
            }
        });
    }

    public function test_should_show_multiple_categories_for_product_edit(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::find(1));

            $product = Product::has("categories", ">", 1)->first();
            $categories = $product->categories->map(fn ($category) => $category->breadcrumbs);

            $browser->visitRoute("products-edit", ["id" => $product->family_prefixed_id])
                ->assertSee("Kategorie")
            ;

            foreach ($categories as $category) {
                $browser->assertSeeIn(".choices", $category);
            }
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
