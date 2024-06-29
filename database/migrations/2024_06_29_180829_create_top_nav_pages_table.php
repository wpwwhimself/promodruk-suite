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
        Schema::create('top_nav_pages', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("slug");
            $table->integer("ordering")->nullable();
            $table->text("content")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('top_nav_pages');
    }
};
