<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QueryFilesCleanerSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::insert([
            [
                "name" => "old_query_files_hours_temporary",
                "label" => "Stare pliki niewysłanych zapytań",
                "group" => "queries",
                "value" => 3,
            ],
            [
                "name" => "old_query_files_hours_sent",
                "label" => "Stare pliki wysłanych zapytań",
                "group" => "queries",
                "value" => 24 * 14,
            ],
        ]);
    }
}
