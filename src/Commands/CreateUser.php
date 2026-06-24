<?php

namespace MyForksFiles\CliPack\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'mff:create:user';

    protected $description = 'create user';

    public function handle()
    {
        parent::handle();

        $this->createuser();

        return Command::SUCCESS;
    }

    public function createUser()
    {
        $users = [
            [
                ['email' => 'cli@laravel.local'],
                ['name' => 'cli', 'password' => Hash::make(uniqid('password_', true))],
            ],
            [
                ['name' => 'user'],
                ['email' => 'user@laravel.local', 'password' => Hash::make(env('APP_USER_PASSWORD'))],
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate($user[0], $user[1]);
        }
    }
}
