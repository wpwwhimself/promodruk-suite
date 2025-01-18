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
        Schema::create('product_families', function (Blueprint $table) {
            $table->string("id")->primary();
            $table->integer("visible")->default(2);
            $table->string("name");
            $table->text("description")->nullable();
            $table->text("extra_description")->nullable();
            $table->string("original_category")->nullable();
            $table->json("images")->nullable();
            $table->json("thumbnails")->nullable();
            $table->json("tabs")->nullable();
            $table->string('related_product_ids')->nullable();
            $table->timestamps();
        });

        Schema::table("products", function (Blueprint $table) {
            // $table->foreign("product_family_id")->references("id")->on("product_families")->cascadeOnDelete()->cascadeOnUpdate();
            $table->dropColumn(["extra_description", "related_product_ids"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_families');

        Schema::table("products", function (Blueprint $table) {
            // $table->dropForeign("products_product_family_id_foreign");
            $table->text("extra_description")->nullable();
            $table->string("related_product_ids")->nullable();
        });
    }
};
