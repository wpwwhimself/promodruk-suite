<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean("product_form_field_amounts_enabled")->default(true)->before("product_form_field_amounts_label");
            $table->boolean("product_form_field_comment_enabled")->default(true)->before("product_form_field_comment_label");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn("product_form_field_amounts_enabled");
            $table->dropColumn("product_form_field_comment_enabled");
        });
    }
};
