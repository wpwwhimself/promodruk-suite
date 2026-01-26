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
        foreach ([
            "app_adaptive_dark_mode" => false,
            "app_logo_path" => "https://wyceny.promovera.pl/media/promodruk_green.svg",
            "app_name" => "Kwazar",
            "users_default_roles[]" => "offer-manager",
            "users_password_reset_mode" => "email",
        ] as $key => $value) {
            Setting::find($key)->update(["value" => $value]);
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
