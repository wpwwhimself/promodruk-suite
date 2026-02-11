<?php

use App\Models\ProductSynchronization;
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
        ProductSynchronization::create([
            "supplier_name" => "Inspirion",
            "product_import_enabled" => true,
            "stock_import_enabled" => true,
            "marking_import_enabled" => true,
            "quickness_priority" => 1,
            "product_import" => [],
            "stock_import" => [],
            "marking_import" => [],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ProductSynchronization::where("supplier_name", "Texet")->delete();
    }
};
