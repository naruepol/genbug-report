<?php

namespace App\Providers;

use App\Http\Controllers\PublicBugController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Resources are passed to Inertia as plain objects instead of {"data": {...}}.
        JsonResource::withoutWrapping();

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Behind a reverse proxy (HTTPS termination), trust it so client IPs (used by the
        // rate limiter) and https:// URLs are correct.
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        $this->configureRateLimiting();
        $this->configureErrorPages();
    }

    private function configureRateLimiting(): void
    {
        // Anti-spam for public reports: by default 5 accepted reports per 10 minutes per IP.
        RateLimiter::for('bug-reports', function (Request $request) {
            return Limit::perMinutes(
                config('bug_reports.rate_limit.window_minutes'),
                config('bug_reports.rate_limit.max_reports'),
            )
                ->by($request->ip())
                // Only accepted reports count; a form with validation errors can be fixed and resent.
                ->after(fn () => $request->attributes->get(PublicBugController::ACCEPTED_ATTRIBUTE) === true)
                ->response(function (Request $request, array $headers) {
                    $message = 'You have sent too many bug reports. Please wait a few minutes and try again.';

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    return back()->withErrors(['form' => $message])->withInput();
                });
        });
    }

    /**
     * Render friendly Inertia error pages; keep Laravel's detailed error page for
     * server errors while debugging locally.
     */
    private function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if (! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return null;
            }

            if ($status >= 500 && config('app.debug')) {
                return null;
            }

            $exception = $response->exception;
            $message = $exception instanceof HttpExceptionInterface && $status < 500
                ? $exception->getMessage()
                : '';

            return $response
                ->render('Error', ['status' => $status, 'message' => $message])
                ->withSharedData();
        });
    }
}
