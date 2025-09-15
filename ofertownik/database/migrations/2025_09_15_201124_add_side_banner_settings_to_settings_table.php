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
        Setting::insert([
            [
                "group" => "side_banner",
                "name" => "side_banner_visible",
                "label" => "Widoczność",
                "value" => "2",
            ],
            [
                "group" => "side_banner",
                "name" => "side_banner_heading",
                "label" => "Treść nagłówka",
                "value" => "NOWOŚCI",
            ],
            [
                "group" => "side_banner",
                "name" => "side_banner_mode",
                "label" => "Tryb wyświetlania",
                "value" => "carousel",
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('group', 'side_banner')->delete();
    }
};
