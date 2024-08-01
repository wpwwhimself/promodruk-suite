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
        Schema::create('main_attributes', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("color");
            $table->text("description")->nullable();
            $table->timestamps();
        });

        Schema::table("products", function (Blueprint $table) {
            $table->foreignId("main_attribute_id")->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_attributes');
        Schema::dropColumns("products", "main_attribute_id");
    }
};
