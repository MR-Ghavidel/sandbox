<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_an_account_with_a_hashed_password_that_can_log_in(): void
    {
        $this->artisan('app:create-user')
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion('Password (at least 12 characters)', 'long-test-password')
            ->expectsQuestion('Confirm password', 'long-test-password')
            ->expectsOutputToContain('Login account created.')
            ->assertSuccessful();

        $user = User::sole();
        $this->assertSame('Owner', $user->name);
        $this->assertSame('owner@example.com', $user->email);
        $this->assertTrue(Hash::check('long-test-password', $user->password));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'long-test-password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_command_rejects_an_existing_email_without_changing_the_account(): void
    {
        $user = User::factory()->create();
        $previousPassword = $user->password;

        $this->artisan('app:create-user')
            ->expectsQuestion('Name', 'Another owner')
            ->expectsQuestion('Email', $user->email)
            ->expectsQuestion('Password (at least 12 characters)', 'another-test-password')
            ->expectsQuestion('Confirm password', 'another-test-password')
            ->expectsOutputToContain('The email has already been taken.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($previousPassword, $user->fresh()->password);
    }

    public function test_command_rejects_a_password_confirmation_mismatch(): void
    {
        $this->artisan('app:create-user')
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion('Password (at least 12 characters)', 'long-test-password')
            ->expectsQuestion('Confirm password', 'different-password')
            ->expectsOutputToContain('The password field confirmation does not match.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_requires_interactive_input(): void
    {
        $this->artisan('app:create-user', ['--no-interaction' => true])
            ->expectsOutputToContain('Run this command interactively')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
