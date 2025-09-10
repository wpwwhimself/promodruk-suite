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
            $table->string("product_form_field_amounts_label")->nullable();
            $table->string("product_form_field_amounts_placeholder")->nullable();
            $table->string("product_form_field_comment_label")->nullable();
            $table->string("product_form_field_comment_placeholder")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn("product_form_field_amounts_label");
            $table->dropColumn("product_form_field_amounts_placeholder");
            $table->dropColumn("product_form_field_comment_label");
            $table->dropColumn("product_form_field_comment_placeholder");
        });
    }
};
