<?php

use App\Models\AltAttribute;
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
        Schema::table('product_families', function (Blueprint $table) {
            $table->json("alt_attributes")->nullable();
            $table->dropForeignIdFor(AltAttribute::class);
            $table->dropColumn("alt_attribute_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_families', function (Blueprint $table) {
            $table->foreignId("alt_attribute_id")->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->dropColumn("alt_attributes");
        });
    }
};
