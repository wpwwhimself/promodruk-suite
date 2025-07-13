<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OfferTest extends DuskTestCase
{
    public function testShouldSeeOfferList(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visitRoute("offers.list")
                ->assertSee("Lista ofert")
                // offer list
                ->with("table", fn ($table) => $table
                    ->assertSee("Edytuj")
                    ->assertSee("Pobierz")
                )
            ;
        });
    }

    public function testShouldInitANewOffer(): void
    {
        $this->browse(function (Browser $browser) {
            $product_id = "AS19061-01";

            $browser->loginAs(1)
                ->visitRoute("offers.offer")
                ->assertSee("Konfiguracja")
                // add product
                ->click("#select2-product-container")
                ->keys(".select2-search__field", $product_id)
                ->waitFor(".select2-results__option--selectable")
                ->click(".select2-results__option--selectable")
                ->waitUntilMissing("#loader")
                ->keys("[name='quantities_maker[$product_id]']", "10", "{enter}")
                ->press("Przelicz ofertę")
                ->waitUntilMissing("#loader")
                ->assertSee("Ilości: 10")
                ->assertSee("Edytuj")
                ->assertSee("Usuń")
                ->click("[role='edit-button']")
                ->assertSee("Wartość produktu netto")
                ->assertSee("10 szt:")
            ;
        });
    }

    public function test_should_see_missing_products_on_old_offer(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visitRoute("offers.offer", ["id" => 47])
                ->assertSee("USUNIĘTY")
            ;
        });
    }

    public function test_should_see_stocks_on_offer(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visitRoute("offers.offer", ["id" => 46])
                ->assertSee("📦")
            ;
        });
    }

    public function test_should_display_correct_calculation_indices_after_deleting_middle_calculation(): void
    {
        $this->browse(function (Browser $browser) {
            $product_id = "AS19061-01";
            $browser->loginAs(1)
                ->visitRoute("offers.offer")
                // add product
                ->click("#select2-product-container")
                ->keys(".select2-search__field", $product_id)
                ->waitFor(".select2-results__option--selectable")
                ->click(".select2-results__option--selectable")
                ->waitUntilMissing("#loader")
                ->keys("[name='quantities_maker[$product_id]']", "10", "{enter}")
                ->press("Przelicz ofertę")
                ->waitUntilMissing("#loader")
                // add first calculation
                ->click("[role='edit-button']")
                ->assertSee("Grawerowanie (G4)")
                ->click("[role='add-to-calculation'][data-marking-id='8546647']")
                ->click("#dialog .button[role='add-calculation'][data-calc-id='new']")
                ->waitUntilMissing("#loader")
                ->assertSee("Kalkulacja nr 1")
                // add second calculation
                ->click("[role='add-to-calculation'][data-marking-id='8546648']")
                ->click("#dialog .button[role='add-calculation'][data-calc-id='new']")
                ->waitUntilMissing("#loader")
                ->assertSee("Kalkulacja nr 2")
                // delete first calculation
                ->click("[role='delete-calculation'][data-marking-id='8546647']")
                ->waitUntilMissing("#loader")
                ->assertDontSee("Kalkulacja nr 2")
            ;
        });
    }
}
