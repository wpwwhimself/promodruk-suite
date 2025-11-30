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
        Setting::insert([
            [
                "name" => "app_logo_front_path",
                "type" => "url",
                "value" => "/storage/meta/logo.png",
            ],
            [
                "name" => "app_favicon_front_path",
                "type" => "url",
                "value" => "/storage/meta/favicon.png",
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
