<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccentColorSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::insert([
            [
                "name" => "app_accent_color_1",
                "label" => "Kolor akcentu 1",
                "group" => "general",
                "value" => "#bfaa40",
            ],
            [
                "name" => "app_accent_color_2",
                "label" => "Kolor akcentu 2",
                "group" => "general",
                "value" => "#d9ca80",
            ],
            [
                "name" => "app_accent_color_3",
                "label" => "Kolor akcentu 3",
                "group" => "general",
                "value" => "#85ca56",
            ],
        ]);
    }
}
