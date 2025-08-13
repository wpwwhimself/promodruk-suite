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
            $table->json("product_import")->nullable();
            $table->json("stock_import")->nullable();
            $table->json("marking_import")->nullable();
            $table->dropColumn([
                "synch_status",
                "current_external_id",
                "progress",
                "last_sync_started_at",
                "last_sync_zero_at",
                "last_sync_completed_at",
                "last_sync_zero_to_full",
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->dropColumn([
                "product_import",
                "stock_import",
                "marking_import",
            ]);
            $table->string("synch_status")->nullable();
            $table->string("current_external_id")->nullable();
            $table->integer("progress")->nullable();
            $table->timestamp("last_sync_started_at")->nullable();
            $table->timestamp("last_sync_zero_at")->nullable();
            $table->timestamp("last_sync_completed_at")->nullable();
            $table->timestamp("last_sync_zero_to_full")->nullable();
        });
    }
};
