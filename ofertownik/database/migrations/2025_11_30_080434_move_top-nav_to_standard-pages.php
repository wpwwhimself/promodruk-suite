<?php

use App\Models\Shipyard\StandardPage;
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
        DB::table("top_nav_pages")->orderBy("id")->get()->each(fn ($page) => StandardPage::create([
            "name" => $page->name,
            "content" => $page->content,
            "visible" => $page->show_in_top_nav ? 2 : 0,
            "order" => $page->ordering,
        ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
