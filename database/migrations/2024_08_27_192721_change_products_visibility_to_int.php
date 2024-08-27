<?php

use App\Models\Category;
use App\Models\Product;
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
        Schema::table('products', function (Blueprint $table) {
            $table->integer("visible")->default(2)->change();
        });
        Product::where("visible", 1)->update(["visible" => 2]);

        Schema::table('categories', function (Blueprint $table) {
            $table->integer("visible")->default(2)->change();
        });
        Category::where("visible", 1)->update(["visible" => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean("visible")->default(true)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean("visible")->default(true)->change();
        });
    }
};
