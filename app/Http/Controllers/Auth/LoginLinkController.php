<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Local development only: signs an allowlisted admin in through a temporary signed
 * URL printed by `php artisan admin:login-link`, so the admin area can be tried
 * before Google OAuth credentials are configured. The route is not registered in
 * other environments.
 */
class LoginLinkController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 403, 'This account is not allowed to access the admin area.');

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::record($user, 'auth.login_link', $user);

        return redirect()->route('admin.dashboard');
    }
}
