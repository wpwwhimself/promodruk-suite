<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password {user?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password of a given user (or all of them), setting them to be equal to their login';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = $this->argument('user');
        $data = ($user)
            ? [User::where('name', $user)->first()]
            : User::all();

        foreach ($data as $user) {
            $user->password = $user->name;
            $user->save();
        }

        $this->info('Password have been reset.');
    }
}
