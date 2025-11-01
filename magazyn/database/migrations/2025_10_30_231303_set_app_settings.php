<?php

use App\Models\Shipyard\Setting;
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
            "app_accent_color_1_dark" => "#0099ff",
            "app_accent_color_1_light" => "#0099ff",
            "app_accent_color_2_dark" => "#0059bf",
            "app_accent_color_2_light" => "#0059bf",
            "app_accent_color_3_dark" => "#37bff9",
            "app_accent_color_3_light" => "#37bff9",
            "app_name" => "Magazyn",
            "app_adaptive_dark_mode" => false,
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
