<?php

use App\Models\AltAttribute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alt_attributes', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("description")->nullable();
            $table->json("variants")->default("[]");
            $table->boolean("large_tiles")->default(false);
            $table->timestamps();
        });

        Schema::table("product_families", function (Blueprint $table) {
            $table->foreignId("alt_attribute_id")->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
        });

        // $table->renameColumn wali błędem...
        DB::statement("ALTER TABLE products CHANGE variant_name variant_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL NULL;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE products CHANGE variant_name variant_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL NULL;");

        Schema::table("product_families", function (Blueprint $table) {
            $table->dropForeignIdFor(AltAttribute::class);
            $table->dropColumn("alt_attribute_id");
        });

        Schema::dropIfExists('alt_attributes');
    }
};
