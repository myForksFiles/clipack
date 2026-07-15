<?php

namespace MyForksFiles\CliPack\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class CreateUser extends Command
{
    protected $signature = 'mff:create:user
                            {--email= : User email}
                            {--name= : User name}
                            {--password= : Plain password (random if omitted)}
                            {--model= : Fully-qualified user model class}';

    protected $description = 'Create or update a user via the configured Eloquent user model';

    public function handle(): int
    {
        $modelClass = $this->resolveModelClass();

        if ($modelClass === null) {
            return self::FAILURE;
        }

        $email = (string) ($this->option('email') ?: $this->ask('Email'));
        $name = (string) ($this->option('name') ?: $this->ask('Name', 'cli'));
        $password = (string) ($this->option('password') ?: '');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email address is required.');

            return self::INVALID;
        }

        if ($name === '') {
            $this->error('Name is required.');

            return self::INVALID;
        }

        if ($password === '') {
            $password = Str::password(16);
            $this->warn('Generated password: '.$password);
        }

        try {
            /** @var class-string<Model> $modelClass */
            $user = $modelClass::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                ]
            );
        } catch (Throwable $e) {
            $this->error('Unable to create user: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('User saved.');
        $this->table(
            ['Field', 'Value'],
            [
                ['model', $modelClass],
                ['id', (string) $user->getKey()],
                ['email', $email],
                ['name', $name],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveModelClass(): ?string
    {
        $modelClass = (string) ($this->option('model') ?: config('clipack.user.model', 'App\\Models\\User'));

        if ($modelClass === '' || ! class_exists($modelClass)) {
            $this->error('User model class was not found: '.$modelClass);
            $this->line('Set CLIPACK_USER_MODEL or pass --model=App\\Models\\User');

            return null;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            $this->error('Configured user model must extend '.Model::class.': '.$modelClass);

            return null;
        }

        return $modelClass;
    }
}
