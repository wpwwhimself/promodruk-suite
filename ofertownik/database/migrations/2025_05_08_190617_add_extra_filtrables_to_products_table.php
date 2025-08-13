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
        Schema::table('products', function (Blueprint $table) {
            $table->json("extra_filtrables")->nullable()->after("sizes");
            $table->string("brand_logo")->nullable()->after("sizes");

            $table->dropColumn(["attributes"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                "extra_filtrables",
                "brand_logo"
            ]);

            $table->json("attributes")->nullable();
        });
    }
};
