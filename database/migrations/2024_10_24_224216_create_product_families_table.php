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
            $table->string("original_sku");
            $table->string("name");
            $table->text("description")->nullable();
            $table->string("source")->nullable();
            $table->string("original_category")->nullable();
            $table->json("image_urls")->nullable();
            $table->json("thumbnail_urls")->nullable();
            $table->json("tabs")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_families');
    }
};
