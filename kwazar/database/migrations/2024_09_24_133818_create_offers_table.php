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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("notes")->nullable();
            $table->json("positions")->nullable();
            $table->timestamps();
            $table->foreignId("created_by")->constrained("users")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId("updated_by")->constrained("users")->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
