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
        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->set("type", ["top-bar", "right-corner"]);
            $table->string("ribbon_color");
            $table->string("ribbon_text");
            $table->integer("ribbon_text_size_pt");
            $table->string("ribbon_text_color");
            $table->boolean("gives_priority_on_listing")->default(false);
            $table->timestamps();
        });

        Schema::create('product_product_tag', function (Blueprint $table) {
            $table->id();
            $table->string("product_family_id");
            $table->foreignId("product_tag_id")->constrained();
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->boolean("disabled")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_tag');
        Schema::dropIfExists('product_tags');
    }
};
