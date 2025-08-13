<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuxiliaryProductsVisibilitySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::insert([
            [
                "group" => "auxiliary_products_visibility",
                "name" => "related_products_visible",
                "label" => "Widoczność produktów powiązanych",
                "value" => "2",
            ],
            [
                "group" => "auxiliary_products_visibility",
                "name" => "similar_products_visible",
                "label" => "Widoczność produktów podobnych",
                "value" => "2",
            ],
        ]);
    }
}
