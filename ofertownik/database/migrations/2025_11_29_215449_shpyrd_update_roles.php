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
        User::where("name", "archmage")->forceDelete();
        User::find(1)->update(["id" => 20]);
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

        User::where("name", "regexp", "edytor")->get()->each(fn ($u) =>
            $u->roles()->attach(["content-manager", "product-manager"])
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
