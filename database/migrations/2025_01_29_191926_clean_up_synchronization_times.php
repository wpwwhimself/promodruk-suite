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
        Schema::table("product_synchronizations", function (Blueprint $table) {
            $table->timestamp("last_sync_zero_at")->after("last_sync_started_at")->nullable();
            $table->bigInteger("last_sync_zero_to_full")->after("last_sync_completed_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("product_synchronizations", function (Blueprint $table) {
            $table->dropColumn("last_sync_zero_at");
            $table->dropColumn("last_sync_zero_to_full");
        });
    }
};
