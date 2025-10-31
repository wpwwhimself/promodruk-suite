<?php

use App\Models\Shipyard\Role;
use App\Models\User;
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
        Role::create([
            "name" => "product-manager",
            "icon" => "package-variant",
            "description" => "Ma dostęp do produktów.",
        ]);

        User::where("name", "administrator")->first()->roles()->attach(["technical", "content-manager", "product-manager"]);
        User::where("name", "edytor")->first()->roles()->attach(["content-manager", "product-manager"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
