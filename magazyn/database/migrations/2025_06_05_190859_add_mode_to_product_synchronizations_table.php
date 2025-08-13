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
            $table->string("module_in_progress")->nullable();
            $table->integer("product_import_enabled")->default(0)->change();
            $table->integer("stock_import_enabled")->default(0)->change();
            $table->integer("marking_import_enabled")->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->dropColumn("module_in_progress");
            $table->boolean("product_import_enabled")->default(false)->change();
            $table->boolean("stock_import_enabled")->default(false)->change();
            $table->boolean("marking_import_enabled")->default(false)->change();
        });
    }
};
