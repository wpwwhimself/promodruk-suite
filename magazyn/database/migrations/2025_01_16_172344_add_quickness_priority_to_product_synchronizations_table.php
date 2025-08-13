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
            $table->integer("quickness_priority")->after("last_sync_started_at")->default(1);
            $table->timestamp("last_sync_completed_at")->after("last_sync_started_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_synchronizations', function (Blueprint $table) {
            $table->dropColumn("quickness_priority");
            $table->dropColumn("last_sync_completed_at");
        });
    }
};
