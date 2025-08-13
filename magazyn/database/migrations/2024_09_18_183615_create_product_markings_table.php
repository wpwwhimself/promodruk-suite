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
        Schema::create('product_markings', function (Blueprint $table) {
            $table->id();
            $table->string("product_id");
                $table->foreign("product_id")->references("id")->on("products")->cascadeOnDelete()->cascadeOnUpdate();
            $table->string("position");
            $table->string("technique");
            $table->string("print_size")->nullable();
            $table->json("images")->nullable();
            $table->json("main_price_modifiers")->nullable();
            $table->json("quantity_prices")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_markings');
    }
};
