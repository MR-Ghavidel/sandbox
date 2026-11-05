<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-user')]
#[Description('Create a login account with a securely prompted password')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('Run this command interactively to enter the account details and password.');

            return self::FAILURE;
        }

        $details = [
            'name' => $this->ask('Name'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($details, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        User::create($validator->safe()->only(['name', 'email', 'password']));

        $this->components->info('Login account created.');

        return self::SUCCESS;
    }
}
