<?php

namespace Tests;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * A user whose email is in the ADMIN_EMAILS allowlist (see phpunit.xml).
     */
    protected function admin(string $email = 'admin@example.com'): User
    {
        return User::factory()->admin($email)->create();
    }

    /**
     * Visit a page the way the Inertia client does after the first load (XHR returning JSON).
     */
    protected function inertiaGet(string $uri): TestResponse
    {
        $version = app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '';

        return $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get($uri);
    }
}
