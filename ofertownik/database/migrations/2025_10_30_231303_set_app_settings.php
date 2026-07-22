<?php

use Wpwwhimself\Shipyard\Models\Setting;
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
        foreach ([
            "app_name" => "Ofertownik",
            "app_adaptive_dark_mode" => false,
            "app_logo_path" => "/media/promodruk_yellow.svg",
        ] as $name => $value) {
            Setting::where("name", $name)->update(["value" => $value]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
