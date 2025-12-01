<?php

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
            "name" => "update-category-ordering",
        ], [
            "name" => "update-category-ordering",
            "visible" => 2,
            "heading" => "Zmień kolejność kategorii",
            "target_route" => "categories-update-ordering",
            "fields" => [
                [
                    "ordering", // name,
                    "number", // type,
                    "Nowa kolejność", // label,
                    "priority-high", // icon,
                    false, // required
                    null, // others
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Modal::where("name", "quest-quote-update")->delete();
    }
};
