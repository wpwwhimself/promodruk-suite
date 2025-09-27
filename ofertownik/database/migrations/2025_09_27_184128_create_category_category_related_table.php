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
        Schema::create('category_category_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId("host_category_id")->constrained("categories", "id")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId("related_category_id")->constrained("categories", "id")->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_category_related');
    }
};
