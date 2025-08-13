<?php

use App\Models\Setting;
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
        Setting::create([
            "name" => "product_refresh_status",
            "label" => "Status odświeżania produktów z Magazynu",
            "group" => "synch",
            "value" => json_encode([
                "enabled" => false,
                "status" => null,
                "current_id" => null,
                "progress" => 0,
                "last_sync_started_at" => null,
                "last_sync_zero_at" => null,
                "last_sync_completed_at" => null,
                "last_sync_zero_to_full" => null,
            ]),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where("name", "product_refresh_status")->delete();
    }
};
