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
        DB::statement(<<<SQL
            alter table products drop index `query_string`;
        SQL);
        DB::statement(<<<SQL
            alter table products modify column query_string varchar(255) character set utf8mb4 collate utf8mb4_polish_ci default null null;
        SQL);
        DB::statement(<<<SQL
            alter table products modify column family_name varchar(255) character set utf8mb4 collate utf8mb4_polish_ci default null null;
        SQL);
        DB::statement(<<<SQL
            alter table products modify column description text character set utf8mb4 collate utf8mb4_polish_ci default null null;
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
        DB::statement(<<<SQL
            alter table products drop index `query_string`;
        SQL);
        DB::statement(<<<SQL
            alter table products modify column query_string varchar(255) character set utf8mb4 collate utf8mb4_unicode_ci default null null;
        SQL);
        DB::statement(<<<SQL
            alter table products modify column family_name varchar(255) character set utf8mb4 collate utf8mb4_unicode_ci default null null;
        SQL);
        DB::statement(<<<SQL
            alter table products modify column description text character set utf8mb4 collate utf8mb4_unicode_ci default null null;
        SQL);
        DB::statement(<<<SQL
            alter table products add fulltext(query_string, family_name, description);
        SQL);
    }
};
