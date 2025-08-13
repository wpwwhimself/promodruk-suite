<?php

namespace Tests\Browser;

use App\Models\Product;
use App\Models\ProductTag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductTagTest extends DuskTestCase
{
    public function testShouldBeAbleToCreateTags(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::whereHas("roles", fn ($q) => $q->where("name", "Edytor"))->first();

            $browser->loginAs($user)
                ->visitRoute("product-tags")
                ->assertSee("Nowy")
                ->clickLink("Nowy")
                ->assertSee("Parametry taga")
                ->type("name", "Tag testowy")
                ->select("type", "w prawym rogu")
                ->type("ribbon_color", "#ff0000")
                ->type("ribbon_text", "Testowy")
                ->type("ribbon_text_size_pt", "12")
                ->type("ribbon_text_color", "#000000")
                ->check("gives_priority_on_listing")
                ->press("Zapisz")
                ->waitFor(".alert", 10)
                ->assertSee("Tag został zapisany")
                ->assertSee("Tag testowy");
        });
    }

    public function testShouldBeAbleToEditTags(): void
    {
        $this->browse(function (Browser $browser) {
            $tag = ProductTag::where("name", "Tag testowy")->first();
            if (!$tag) return;

            $browser->visitRoute("product-tags-edit", ["id" => $tag->id])
                ->assertSee("Parametry taga")
                ->type("ribbon_text", "Testowy edytowany")
                ->press("Zapisz")
                ->waitFor(".alert")
                ->assertSee("Tag został zapisany")
                ->assertSee("Testowy edytowany");
        });
    }

    public function testShouldBeAbleToApplyTags(): void
    {
        $this->browse(function (Browser $browser) {
            $tag = ProductTag::where("name", "Tag testowy")->first();
            if (!$tag) return;
            $product = Product::whereHas("categories")->get()->random();
            Log::debug("🧪 testing: applying product tags - product: $product->id $product->name");

            $browser->visitRoute("products-edit", ["id" => $product->family_prefixed_id])
                ->assertSee("Tagi")
                ->select("new_tag[id]", $tag->id)
                ->type("new_tag[start_date]", Carbon::today()->subMonth()->format("dmY"))
                ->press("Dodaj")
                ->waitFor(".alert", 10)
                ->assertSee("Produkt został zapisany")
                ->assertSee((string) $tag);
            $browser->visitRoute("category-".$product->categories->first()->id)
                ->assertSee($tag->ribbon_text);
        });
    }

    public function testShouldBeAbleToDeleteTags(): void
    {
        $this->browse(function (Browser $browser) {
            $tag = ProductTag::where("name", "Tag testowy")->first();
            if (!$tag) return;

            $browser->visitRoute("product-tags-edit", ["id" => $tag->id])
                ->assertSee("Parametry taga")
                ->press("Usuń")
                ->waitForDialog(10)
                // ->assertDialogOpened("Ostrożnie! Czy na pewno chcesz to zrobić?")
                ->acceptDialog()
                ->waitFor(".alert", 10)
                ->assertSee("Tag został usunięty")
                ->assertDontSee("Tag testowy");
        });
    }
}
