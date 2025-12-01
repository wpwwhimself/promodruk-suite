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
        $tmp = DB::table("tmp_settings")->get();
        $types = [
            "old_query_files_hours_sent" => "number",
            "old_query_files_hours_temporary" => "number",
            "related_products_visible" => "select",
            "similar_products_visible" => "select",
            "showcase_visible" => "select",
            "showcase_mode" => "select",
            "showcase_full_width_text" => "HTML",
            "showcase_side_text" => "HTML",
            "side_banner_visible" => "select",
            "side_banner_mode" => "select",
            "side_banner_heading" => "text",
            "welcome_text_visible" => "select",
            "welcome_text_content" => "HTML",
        ];
        foreach ($types as $key => $type) {
            DB::table("settings")->insert([
                "name" => $key,
                "type" => $type,
                "value" => $tmp->firstWhere("name", $key)->value,
            ]);
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
