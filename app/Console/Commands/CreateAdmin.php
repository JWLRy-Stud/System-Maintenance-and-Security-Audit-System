<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates an active administrator. The password is asked with a hidden prompt (never a command-line argument or
 * option), so it does not end up in shell or tinker history. Use this for the first admin on a new deployment.
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--name= : Full name} {--email= : Email address}';

    protected $description = 'Create an active administrator account (asks for the password with a hidden prompt)';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        $password = $this->secret('Password');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => ['required', 'string', 'confirmed', User::passwordRule()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = (new User(['name' => $name, 'email' => $email, 'password' => $password]));
        $user->forceFill(['role' => 'admin', 'status' => 'active'])->save(); // role/status are not mass-assignable
        ActivityLogService::record('user.created', $user, 'success', null, 'created with artisan app:create-admin');

        $this->info("Administrator {$email} created.");

        return self::SUCCESS;
    }
}
