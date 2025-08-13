<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                "global_products_discount",
                "global_markings_discount",
                "global_surcharge",
            ]);
            $table->json("default_discounts")->nullable();
            $table->float("default_surcharge", 8, 1, true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->float("global_products_discount", 8, 1, true)->default(0);
            $table->float("global_markings_discount", 8, 1, true)->default(0);
            $table->float("global_surcharge", 8, 1, true)->default(0);

            $table->dropColumn(["default_discounts", "default_surcharge"]);
        });
    }
};
