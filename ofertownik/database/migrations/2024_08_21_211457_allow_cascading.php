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
        Schema::table("categories", function (Blueprint $table) {
            $table->dropForeign("categories_parent_id_foreign");
            $table->foreign("parent_id")
                ->references("id")->on("categories")
                ->onUpdate("cascade")->onDelete("cascade");
        });

        Schema::table("category_product", function (Blueprint $table) {
            $table->dropForeign("category_product_category_id_foreign");
            $table->foreign("category_id")
                ->references("id")->on("categories")
                ->onUpdate("cascade")->onDelete("cascade");
            $table->dropForeign("category_product_product_id_foreign");
            $table->foreign("product_id")
                ->references("id")->on("products")
                ->onUpdate("cascade")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("categories", function (Blueprint $table) {
            $table->dropForeign("categories_parent_id_foreign");
            $table->foreign("parent_id")
                ->references("id")->on("categories")
                ->onUpdate("restrict")->onDelete("restrict");
        });

        Schema::table("category_product", function (Blueprint $table) {
            $table->dropForeign("category_product_category_id_foreign");
            $table->foreign("category_id")
                ->references("id")->on("categories")
                ->onUpdate("restrict")->onDelete("restrict");
            $table->dropForeign("category_product_product_id_foreign");
            $table->foreign("product_id")
                ->references("id")->on("products")
                ->onUpdate("restrict")->onDelete("restrict");
        });
    }
};
