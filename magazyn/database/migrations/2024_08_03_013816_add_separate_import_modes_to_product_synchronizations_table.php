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
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->boolean("product_import_enabled")->default(false);
            $table->boolean("stock_import_enabled")->default(false);
            $table->dropColumn("enabled");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->boolean("enabled")->default(false);
            $table->dropColumn("product_import_enabled");
            $table->dropColumn("stock_import_enabled");
        });
    }
};
