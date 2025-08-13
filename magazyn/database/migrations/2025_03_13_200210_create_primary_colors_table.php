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
        Schema::create('primary_colors', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("color");
            $table->text("description")->nullable();
            $table->timestamps();
        });

        Schema::table("main_attributes", function (Blueprint $table) {
            $table->foreignId("primary_color_id")->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("main_attributes", function (Blueprint $table) {
            $table->dropForeign("main_attributes_primary_color_id_foreign");
            $table->dropColumn("primary_color_id");
        });
        Schema::dropIfExists('primary_colors');
    }
};
