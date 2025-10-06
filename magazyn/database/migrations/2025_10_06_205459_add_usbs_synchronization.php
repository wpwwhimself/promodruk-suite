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
            "supplier_name" => "USBSystem",
            "product_import_enabled" => true,
            "stock_import_enabled" => false,
            "marking_import_enabled" => false,
            "quickness_priority" => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ProductSynchronization::where("supplier_name", "USBSystem")->delete();
    }
};
