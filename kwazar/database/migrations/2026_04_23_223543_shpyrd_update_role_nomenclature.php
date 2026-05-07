<?php

use App\Models\Shipyard\Role;
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
        Role::find("offer-maker")->update(["name" => "offer-manager"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::find("offer-manager")->update(["name" => "offer-maker"]);
    }
};
