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
        Schema::table("products", function (Blueprint $table) {
            $table->string("query_string")->nullable()->after("family_name");
        });

        DB::statement(<<<SQL
            alter table products drop index `name`;
        SQL);
        DB::statement(<<<SQL
            alter table products add fulltext(query_string, family_name, description);
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("products", function (Blueprint $table) {
            $table->dropColumn("query_string");
        });

        DB::statement(<<<SQL
            alter table products drop fulltext query_string;
        SQL);
        DB::statement(<<<SQL
            alter table products add fulltext(name, family_name, description, front_id);
        SQL);
    }
};
