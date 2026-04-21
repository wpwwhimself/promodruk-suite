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
        Role::where("name", "offer-manager")->update(["description" => "Ma dostęp do swoich ofert"]);
        Role::create([
            "name" => "offer-master",
            "icon" => "file-plus",
            "description" => "Ma dostęp do wszystkich ofert",
        ]);
        User::find(2)->roles()->attach(["offer-master"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::find("offer-master")->delete();
    }
};
