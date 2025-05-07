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
        Schema::drop('attribute_product');
        Schema::drop('variants');
        Schema::drop('attributes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("type");
            $table->timestamps();
        });

        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId("attribute_id")->constrained()->cascadeOnDelete();
            $table->string("name");
            $table->string("value");
            $table->timestamps();
        });

        Schema::create('attribute_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId("attribute_id")->constrained();
            $table->string("product_id");
                $table->foreign("product_id")->references("id")->on("products");
            $table->timestamps();
        });
    }
};
