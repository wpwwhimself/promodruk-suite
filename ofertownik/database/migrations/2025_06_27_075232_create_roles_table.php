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
        Schema::create('roles', function (Blueprint $table) {
            $table->string("name")->primary();
            $table->text("description");
        });

        DB::table("roles")->insert([
            [
                "name" => "Administrator",
                "description" => "Ma dostęp do ustawień systemu",
            ],
            [
                "name" => "Edytor",
                "description" => "Ma dostęp do stron, kategorii, produktów i plików",
            ],
        ]);

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->cascadeOnDelete()->cascadeOnUpdate();
            $table->string("role_name");
                $table->foreign("role_name")->references("name")->on("roles")->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        DB::table("role_user")->insert([
            ["user_id" => 1, "role_name" => "Administrator"],
            ["user_id" => 1, "role_name" => "Edytor"],
            ["user_id" => 2, "role_name" => "Administrator"],
            ["user_id" => 2, "role_name" => "Edytor"],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
