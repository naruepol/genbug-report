<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the request only for a signed-in user whose email is in ADMIN_EMAILS.
 */
class EnsureAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isAdmin() ?? false,
            403,
            'Your account is not allowed to access the admin area.',
        );

        return $next($request);
    }
}
