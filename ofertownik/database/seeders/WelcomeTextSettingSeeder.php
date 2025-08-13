<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WelcomeTextSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::insert([
            [
                "name" => "welcome_text_content",
                "label" => "Treść",
                "group" => "welcome_text",
                "value" => "",
            ],
            [
                "name" => "welcome_text_visible",
                "label" => "Widoczność",
                "group" => "welcome_text",
                "value" => "0",
            ],
        ]);
    }
}
