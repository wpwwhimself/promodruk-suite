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
        User::find(1)->update(["id" => 9]);
        $super = User::where("name", "Super")->first();
        $super->update([
            "id" => 1,
            "name" => "archmage",
        ]);
        $super->roles()->sync(["archmage"]);

        User::find(2)->roles()->attach(["technical"]);

        User::all()->each(fn ($u) =>
            $u->roles()->attach(["offer-manager"])
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
