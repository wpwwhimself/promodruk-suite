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
        Schema::create("tmp_settings", function (Blueprint $table) {
            $table->string("name")->primary();
            $table->text("value")->nullable();
        });
        DB::table("settings")->get()->each(fn ($s) => DB::table("tmp_settings")->insert([
            "name" => $s->name,
            "value" => $s->value,
        ]));
        Schema::dropIfExists("settings");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
