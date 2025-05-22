<?php

use App\Models\MainAttribute;
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
            $table->dropForeignIdFor(MainAttribute::class);
            $table->dropColumn("main_attribute_id");
            $table->string("variant_name")->nullable()->after("original_category");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId("main_attribute_id")->nullable()->constrained();
            $table->dropColumn("variant_name");
        });
    }
};
