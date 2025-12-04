<?php

use App\Models\ProductTag;
use App\Models\Shipyard\Modal;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Modal::updateOrCreate([
            "name" => "update-tag-for-products",
        ], [
            "name" => "update-tag-for-products",
            "visible" => 2,
            "heading" => "Zaktualizuj tag produktu",
            "target_route" => "product-tag-update-for-products",
            "fields" => [
                [
                    "tag_id", // name,
                    "select", // type,
                    "Tag", // label,
                    "tag", // icon,
                    true, // required
                    [
                        "selectData" => [
                            "optionsFromScope" => [
                                ProductTag::class,
                                "ordered",
                            ],
                        ],
                    ], // others
                ],
                [
                    "start_date", // name,
                    "date", // type,
                    "Widoczny od", // label,
                    "calendar-start", // icon,
                    false, // required
                ],
                [
                    "end_date", // name,
                    "date", // type,
                    "Widoczny do", // label,
                    "calendar-end", // icon,
                    false, // required
                ],
                [
                    "disabled", // name,
                    "checkbox", // type,
                    "Zawieszony", // label,
                    "tag-off", // icon,
                    false, // required
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Modal::where("name", "update-tag-for-products")->delete();
    }
};
