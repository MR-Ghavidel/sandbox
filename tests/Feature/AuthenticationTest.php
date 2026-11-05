<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_redirects_to_tools_without_showing_the_dashboard(): void
    {
        $this->get(route('home'))->assertRedirect(route('tools.index'));
    }

    public function test_login_page_has_a_form_and_public_tools_navigation(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ورود به حساب')
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('href="'.route('tools.json').'"', false)
            ->assertDontSee('href="'.route('tasks.index').'"', false);
    }

    public function test_valid_credentials_log_in_regenerate_session_and_redirect_home(): void
    {
        $user = User::factory()->create();
        $this->get(route('login'));
        $previousSessionId = session()->getId();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->get(route('home'))->assertOk()->assertSee('کارهای امروز');
    }

    public function test_login_returns_user_to_the_requested_private_page(): void
    {
        $user = User::factory()->create();
        $this->get(route('notes.index'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('notes.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_me_sets_a_persistent_login_cookie(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('home'))
            ->assertCookie(Auth::guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_incorrect_password_shows_generic_error_and_does_not_flash_password(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'ایمیل یا رمز عبور نادرست است.'])
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_failed_login_displays_the_error_on_the_login_page(): void
    {
        $this->followingRedirects()->from(route('login'))
            ->post(route('login.store'), ['email' => 'missing@example.com', 'password' => 'wrong-password'])
            ->assertSee('ایمیل یا رمز عبور نادرست است.')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_unknown_email_shows_the_same_generic_error(): void
    {
        $this->post(route('login.store'), ['email' => 'missing@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'ایمیل یا رمز عبور نادرست است.']);

        $this->assertGuest();
    }

    #[DataProvider('invalidLoginDetails')]
    public function test_invalid_login_details_are_rejected(array $details, string $field, string $message): void
    {
        $this->post(route('login.store'), $details)->assertSessionHasErrors([$field => $message]);

        $this->assertGuest();
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidLoginDetails(): array
    {
        return [
            'missing email' => [['password' => 'password'], 'email', 'ایمیل را وارد کنید.'],
            'invalid email' => [['email' => 'invalid', 'password' => 'password'], 'email', 'ایمیل معتبر وارد کنید.'],
            'invalid email type' => [['email' => ['user@example.com'], 'password' => 'password'], 'email', 'ایمیل معتبر وارد کنید.'],
            'long email' => [['email' => str_repeat('a', 244).'@example.com', 'password' => 'password'], 'email', 'ایمیل نباید بیشتر از ۲۵۵ کاراکتر باشد.'],
            'missing password' => [['email' => 'user@example.com'], 'password', 'رمز عبور را وارد کنید.'],
            'invalid password type' => [['email' => 'user@example.com', 'password' => ['password']], 'password', 'رمز عبور معتبر وارد کنید.'],
            'invalid remember' => [['email' => 'user@example.com', 'password' => 'password', 'remember' => 'yes'], 'remember', 'گزینه مرا به خاطر بسپار معتبر نیست.'],
        ];
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect(route('home'));
    }

    public function test_logout_invalidates_session_and_returns_to_public_tools(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));
        $previousToken = session()->token();

        $this->withSession(['private_marker' => 'private'])->post(route('logout'))
            ->assertRedirect(route('tools.index'))
            ->assertSessionMissing('private_marker');

        $this->assertGuest();
        $this->assertNotSame($previousToken, session()->token());
        $this->get(route('notes.index'))->assertRedirect(route('login'));
        $this->get(route('tools.index'))->assertOk()->assertDontSee('action="'.route('logout').'"', false);
    }

    #[DataProvider('privateEndpoints')]
    public function test_private_endpoints_return_401_for_guest_json_requests(string $method, string $path): void
    {
        $this->json($method, $path)->assertUnauthorized();

        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('sites', 0);
        $this->assertDatabaseCount('attendance_imports', 0);
        $this->assertDatabaseCount('site_imports', 0);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function privateEndpoints(): array
    {
        return [
            'home' => ['GET', '/'],
            'logout' => ['POST', '/logout'],
            'task list' => ['GET', '/tasks'],
            'create task' => ['POST', '/tasks'],
            'update task' => ['PATCH', '/tasks/1'],
            'delete task' => ['DELETE', '/tasks/1'],
            'order tasks' => ['PATCH', '/tasks/order'],
            'carry over tasks' => ['POST', '/tasks/carry-over'],
            'payroll index' => ['GET', '/payroll'],
            'payroll month' => ['GET', '/payroll/1405/8'],
            'payroll settings' => ['PUT', '/payroll/1405/8/settings'],
            'payroll day' => ['PUT', '/payroll/1405/8/days/2026-11-01'],
            'note list' => ['GET', '/notes'],
            'create note form' => ['GET', '/notes/create'],
            'create note' => ['POST', '/notes'],
            'view note' => ['GET', '/notes/1'],
            'edit note' => ['GET', '/notes/1/edit'],
            'update note' => ['PUT', '/notes/1'],
            'delete note' => ['DELETE', '/notes/1'],
            'pin note' => ['PATCH', '/notes/1/pin'],
            'preview note' => ['POST', '/notes/preview'],
            'site list' => ['GET', '/sites'],
            'create site' => ['POST', '/sites'],
            'edit site' => ['GET', '/sites/1/edit'],
            'update site' => ['PUT', '/sites/1'],
            'delete site' => ['DELETE', '/sites/1'],
            'pin site' => ['PATCH', '/sites/1/pin'],
            'open site' => ['GET', '/sites/1/open'],
            'site icon' => ['GET', '/sites/1/icon'],
            'import sites' => ['POST', '/site-imports'],
            'view site import' => ['GET', '/site-imports/1'],
            'apply site import' => ['POST', '/site-imports/1/apply'],
            'import attendance' => ['POST', '/attendance-imports'],
            'import excel' => ['POST', '/attendance-imports/excel'],
            'view attendance import' => ['GET', '/attendance-imports/1'],
            'apply attendance import' => ['POST', '/attendance-imports/1/apply'],
        ];
    }
}
