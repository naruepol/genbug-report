<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Prints a short-lived sign-in link for an allowlisted admin (local development only),
 * for trying the admin area before Google OAuth is configured.
 */
#[Signature('admin:login-link {email? : Admin email (defaults to the first ADMIN_EMAILS entry)}')]
#[Description('Print a temporary admin sign-in link (local environment only)')]
class AdminLoginLink extends Command
{
    public function handle(): int
    {
        if (! Route::has('auth.login-link')) {
            $this->error('Login links are only available when APP_ENV=local.');

            return self::FAILURE;
        }

        $email = strtolower((string) ($this->argument('email') ?? config('admin.emails.0')));

        if (! User::isAllowlisted($email)) {
            $this->error("{$email} is not listed in ADMIN_EMAILS.");

            return self::FAILURE;
        }

        $user = User::firstOrCreate(['email' => $email], ['name' => Str::headline(Str::before($email, '@'))]);

        $this->line('Open this link within 15 minutes to sign in as '.$email.':');
        $this->newLine();
        $path = URL::temporarySignedRoute('auth.login-link', now()->addMinutes(15), ['user' => $user->id], absolute: false);
        $this->line(rtrim((string) config('app.url'), '/').$path);

        return self::SUCCESS;
    }
}
