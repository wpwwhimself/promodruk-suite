<?php

use App\Models\User;
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
        User::where("name", "archmage")->forceDelete();
        User::find(1)->update(["id" => 9]);
        $super = User::where("name", "Super")->first();
        $super->update([
            "id" => 1,
            "name" => "archmage",
        ]);
        DB::table("role_user")->insert([
            "user_id" => $super->id,
            "role_name" => "archmage",
        ]);

        DB::table("role_user")->insert([
            "user_id" => 2,
            "role_name" => "technical",
        ]);

        User::all()->each(fn ($u) =>
            DB::table("role_user")->insert([
                "user_id" => $u->id,
                "role_name" => "offer-manager",
            ])
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
