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

        Schema::create('domain_pages', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->integer("visible");
            $table->integer("order");

            $table->text("content")->nullable();

            $table->foreignId("created_by")->nullable()->constrained("users")->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId("updated_by")->nullable()->constrained("users")->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId("deleted_by")->nullable()->constrained("users")->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create("domain_domain_page", function (Blueprint $table) {
            $table->id();
            $table->foreignId("domain_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("domain_page_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_supervisors');
        Schema::dropIfExists('domain_domain_page');
        Schema::dropIfExists('domain_pages');
    }
};
