<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\AbstractUser as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Admin sign-in with Google OAuth. Only emails in ADMIN_EMAILS are accepted.
 */
class GoogleController extends Controller
{
    public function login(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Auth/Login', [
            'googleConfigured' => filled(config('services.google.client_id'))
                && filled(config('services.google.client_secret')),
        ]);
    }

    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            Inertia::flash('error', 'Google sign-in failed. Please try again.');

            return redirect()->route('login');
        }

        abort_unless(
            $this->isVerifiedEmail($googleUser) && User::isAllowlisted($googleUser->getEmail()),
            403,
            'This Google account is not allowed to access the admin area.',
        );

        $user = $this->syncUser($googleUser);

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::record($user, 'auth.login', $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function isVerifiedEmail(SocialiteUser $googleUser): bool
    {
        $raw = $googleUser->getRaw();

        return filled($googleUser->getEmail())
            && (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);
    }

    /**
     * Create or update the admin record, matching by Google id first and then by email.
     */
    private function syncUser(SocialiteUser $googleUser): User
    {
        $email = strtolower((string) $googleUser->getEmail());

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first() ?? new User;

        $user->fill([
            'google_id' => $googleUser->getId(),
            'email' => $email,
            'name' => $googleUser->getName() ?: $email,
            'avatar_url' => $googleUser->getAvatar(),
        ])->save();

        return $user;
    }
}
