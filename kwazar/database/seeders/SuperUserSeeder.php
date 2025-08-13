<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            "name" => "Super",
            "login" => "super",
            "password" => Hash::make("super"),
        ]);
        $user->roles()->attach([
            "offer-maker",
            "offer-master",
            "technical",
        ]);
    }
}
