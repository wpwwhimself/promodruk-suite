<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShowcaseSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::insert([
            [
                "name" => "showcase_visible",
                "label" => "Pokaz widoczny",
                "group" => "showcase",
                "value" => "2",
            ],
            [
                "name" => "showcase_top_heading",
                "label" => "Treść nagłówka",
                "group" => "showcase",
                "value" => "Ponad 1000 nowości już dostępne!",
            ],
            [
                "name" => "showcase_side_text",
                "label" => "Treść tekstu bocznego",
                "group" => "showcase",
                "value" => "A to niektóre z nich:",
            ],
            [
                "name" => "showcase_content",
                "label" => "Link/embed do filmu pokazowego",
                "group" => "showcase",
                "value" => "",
            ],
        ]);
    }
}
