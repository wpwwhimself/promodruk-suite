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
        Schema::create('product_synchronizations', function (Blueprint $table) {
            $table->id();
            $table->string("supplier_name");
            $table->boolean("enabled")->default(false);
            $table->date("last_sync_started_at")->nullable();
            $table->float("progress")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_synchronizations');
    }
};
