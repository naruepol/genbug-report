<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(array $attributes = []): GoogleUser
    {
        return GoogleUser::fake([
            'id' => 'google-123',
            'name' => 'Admin Person',
            'email' => 'admin@example.com',
            'email_verified' => true,
            'avatar' => 'https://example.com/avatar.png',
            ...$attributes,
        ]);
    }

    public function test_guests_see_the_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('googleConfigured', true));
    }

    public function test_guests_are_redirected_from_the_admin_area_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/projects')->assertRedirect('/login');
        $this->get('/admin/bugs')->assertRedirect('/login');
    }

    public function test_sign_in_redirects_to_google(): void
    {
        $response = $this->get('/auth/google');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $location);
        $this->assertStringContainsString('client_id=test-client-id', $location);
        $this->assertStringContainsString(urlencode('http://localhost:8080/auth/google/callback'), $location);
    }

    public function test_allowlisted_google_account_signs_in_and_reaches_the_dashboard(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser());

        $this->get('/auth/google/callback')->assertRedirect('/admin');

        $this->assertAuthenticated();
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('Admin Person', $user->name);
        $this->assertSame('https://example.com/avatar.png', $user->avatar_url);
        $this->assertDatabaseHas('audit_logs', ['admin_id' => $user->id, 'action' => 'auth.login']);

        $this->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
    }

    public function test_second_allowlisted_email_can_also_sign_in(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser(['id' => 'google-456', 'email' => 'teacher@example.com']));

        $this->get('/auth/google/callback')->assertRedirect('/admin');

        $this->assertAuthenticated();
    }

    public function test_allowlist_matching_ignores_email_case(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser(['email' => 'Admin@Example.COM']));

        $this->get('/auth/google/callback')->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    public function test_google_account_not_in_allowlist_gets_403_and_is_not_signed_in(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser(['email' => 'stranger@example.com']));

        $this->get('/auth/google/callback')->assertForbidden();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser(['email_verified' => false]));

        $this->get('/auth/google/callback')->assertForbidden();

        $this->assertGuest();
    }

    public function test_existing_admin_record_is_updated_on_sign_in(): void
    {
        $existing = User::factory()->admin()->create(['google_id' => null, 'name' => 'Old Name']);

        Socialite::fake('google', $this->fakeGoogleUser());

        $this->get('/auth/google/callback')->assertRedirect('/admin');

        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame('google-123', $existing->fresh()->google_id);
        $this->assertSame('Admin Person', $existing->fresh()->name);
        $this->assertSame(1, User::count());
    }

    public function test_failed_google_callback_returns_to_login_with_an_error(): void
    {
        Socialite::fake('google', fn () => throw new InvalidStateException);

        $this->get('/auth/google/callback')
            ->assertRedirect('/login')
            ->assertInertiaFlash('error', 'Google sign-in failed. Please try again.');

        $this->assertGuest();
    }

    public function test_user_removed_from_the_allowlist_loses_admin_access(): void
    {
        $user = User::factory()->create(['email' => 'former-admin@example.com']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/projects')->assertForbidden();
    }

    public function test_signed_in_admin_visiting_login_goes_to_the_dashboard(): void
    {
        $this->actingAs($this->admin())->get('/login')->assertRedirect('/admin');
    }

    public function test_admin_can_sign_out(): void
    {
        $this->actingAs($this->admin())->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_local_login_link_signs_in_an_allowlisted_admin(): void
    {
        $admin = $this->admin();
        $url = URL::temporarySignedRoute('auth.login-link', now()->addMinutes(15), ['user' => $admin->id], absolute: false);

        $this->get($url)->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_link_command_prints_a_working_link(): void
    {
        $this->artisan('admin:login-link')
            ->expectsOutputToContain('http://localhost:8080/auth/login-link/')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);

        $this->artisan('admin:login-link stranger@example.com')->assertFailed();
    }

    public function test_expired_login_link_is_rejected(): void
    {
        $admin = $this->admin();
        $url = URL::temporarySignedRoute('auth.login-link', now()->addMinutes(15), ['user' => $admin->id], absolute: false);

        $this->travel(16)->minutes();

        $this->get($url)->assertForbidden();
        $this->assertGuest();
    }

    public function test_login_link_requires_a_valid_signature_and_an_allowlisted_user(): void
    {
        $admin = $this->admin();

        $this->get("/auth/login-link/{$admin->id}")->assertForbidden();
        $this->assertGuest();

        $stranger = User::factory()->create(['email' => 'stranger@example.com']);
        $url = URL::temporarySignedRoute('auth.login-link', now()->addMinutes(15), ['user' => $stranger->id], absolute: false);

        $this->get($url)->assertForbidden();
        $this->assertGuest();
    }
}
