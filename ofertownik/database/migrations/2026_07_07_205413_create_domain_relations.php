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
        Schema::create("domain_supervisor", function (Blueprint $table) {
            $table->id();
            $table->foreignId("domain_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("supervisor_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create("domain_standard_page", function (Blueprint $table) {
            $table->id();
            $table->foreignId("domain_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("standard_page_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_supervisor');
        Schema::dropIfExists('domain_standard_pages');
    }
};
