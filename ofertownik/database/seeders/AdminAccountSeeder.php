<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ["name" => "administrator"],
            ["password" => Hash::make("administrator")]
        );
        User::updateOrCreate(
            ["name" => "super"],
            ["password" => Hash::make("super")]
        );
    }
}
