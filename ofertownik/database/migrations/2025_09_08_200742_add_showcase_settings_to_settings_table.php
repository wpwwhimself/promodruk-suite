<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table("settings")->insert([
            [
                "group" => "showcase",
                "name" => "showcase_mode",
                "label" => "Tryb wyświetlania",
                "value" => "film",
            ],
            [
                "group" => "showcase",
                "name" => "showcase_full_width_text",
                "label" => "Treść tekstu dla pełnej szerokości",
                "value" => null,
            ]
        ]);

        DB::table("settings")->where("name", "showcase_top_heading")->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table("settings")->whereIn("name", [
            "showcase_mode",
            "showcase_full_width_text",
        ])->delete();

        DB::table("settings")->insert([
            [
                "group" => "showcase",
                "name" => "showcase_top_heading",
                "label" => "Treść nagłówka",
                "value" => null,
            ],
        ]);
    }
};
