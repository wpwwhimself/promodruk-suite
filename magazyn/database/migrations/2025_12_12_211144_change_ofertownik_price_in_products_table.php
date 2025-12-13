<?php

use App\Models\CustomSupplier;
use App\Models\Product;
use App\Models\Shipyard\Modal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn("ofertownik_price", "ofertownik_price_multiplier");
        });

        Product::whereNotNull("ofertownik_price_multiplier")
            ->update([
                "ofertownik_price_multiplier" => null,
            ]);

        // initial multipliers
        Product::whereHas("productFamily", fn ($q) => $q
            ->where("source", "Midocean")
        )
            ->update([
                "ofertownik_price_multiplier" => 2.3,
            ]);

        Product::whereHas("productFamily", fn ($q) => $q
            ->where("source", "Easygifts")
        )
            ->where("name", "regexp", "pierre cardin")
            ->update([
                "ofertownik_price_multiplier" => 2,
            ]);

        Modal::updateOrCreate([
            "name" => "set-ofertownik-price-multiplier-for-families",
        ], [
            "name" => "set-ofertownik-price-multiplier-for-families",
            "visible" => 1,
            "heading" => "Nadaj nowe mnożniki",
            "target_route" => "products.ofertownik-price-multipliers.process",
            "fields" => [
                [
                    "new_multiplier", // name,
                    "number", // type,
                    "Nowy mnożnik", // label,
                    "multiplication", // icon,
                    false, // required
                    [
                        "min" => 0,
                        "step" => 0.01,
                        "hint" => "Podaj nowy mnożnik. Pozostaw puste, żeby usunąć aktualny wpis."
                    ], // others
                ],
            ],
        ]);

        Modal::updateOrCreate([
            "name" => "add-ofertownik-price-multiplier",
        ], [
            "name" => "add-ofertownik-price-multiplier",
            "visible" => 1,
            "heading" => "Nadaj mnożnik dla produktów",
            "target_route" => "products.ofertownik-price-multipliers.process",
            "fields" => [
                [
                    null,
                    "heading",
                    "Które produkty otrzymają mnożnik",
                    model_icon('product-families'),
                    false,
                ],
                [
                    null,
                    "paragraph",
                    "Wszystkie poniższe warunki muszą zostać spełnione. Pozostaw puste, aby pominąć.",
                    "lightbulb-question",
                    false,
                    [
                        "class" => "ghost",
                    ]
                ],
                [
                    "id", // name,
                    "text", // type,
                    "SKU", // label,
                    "barcode", // icon,
                    false, // required
                ],
                [
                    "name", // name,
                    "text", // type,
                    "Nazwa", // label,
                    model_field_icon('product-families', 'name'), // icon,
                    false, // required
                ],
                [
                    "supplier", // name,
                    "select", // type,
                    "Dostawca", // label,
                    model_icon('custom-suppliers'), // icon,
                    false, // required
                    [
                        "selectData" => [
                            "optionsFromStatic" => [
                                CustomSupplier::class,
                                'allSuppliers',
                            ],
                            "emptyOption" => "Wszyscy",
                        ],
                    ],
                ],
                [
                    null,
                    "heading",
                    "Mnożnik",
                    "multiplication",
                    false,
                ],
                [
                    "new_multiplier", // name,
                    "number", // type,
                    "Nowy mnożnik", // label,
                    "multiplication", // icon,
                    false, // required
                    [
                        "min" => 0,
                        "step" => 0.01,
                        "hint" => "Podaj nowy mnożnik. Pozostaw puste, żeby usunąć aktualny wpis."
                    ], // others
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn("ofertownik_price_multiplier", "ofertownik_price");
        });

        Product::whereNotNull("ofertownik_price")
            ->update([
                "ofertownik_price" => null,
            ]);

        // initial multipliers
        Product::whereHas("productFamily", fn ($q) => $q
            ->where("source", "Midocean")
        )
            ->get()
            ->each(fn ($p) => $p->update([
                "ofertownik_price" => $p->price * 2.3,
            ]));

        Product::whereHas("productFamily", fn ($q) => $q
            ->where("source", "Easygifts")
        )
            ->where("name", "regexp", "pierre cardin")
            ->get()->each(fn ($p) => $p->update([
                "ofertownik_price" => $p->price * 2,
            ]));

        Modal::where("name", "set-ofertownik-price-multiplier-for-families")->delete();
        Modal::where("name", "add-ofertownik-price-multiplier")->delete();
    }
};
