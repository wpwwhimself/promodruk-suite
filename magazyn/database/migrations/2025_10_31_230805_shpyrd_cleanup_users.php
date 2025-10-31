<?php

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
        User::where("name", "archmage")->delete();
        User::find(1)->update(["id" => 4]);
        $super = User::where("name", "super")->first();
        $super->update([
            "id" => 1,
            "name" => "archmage",
        ]);
        $super->roles()->sync(["archmage"]);

        User::where("name", "administrator")->first()->roles()->attach([
            "technical",
            "product-manager",
            "content-manager",
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
