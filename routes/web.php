<?php

use App\Http\Controllers\Admin\BugController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginLinkController;
use App\Http\Controllers\PublicBugController;
use App\Http\Controllers\PublicProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes (no login)
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicProjectController::class, 'home'])->name('home');

Route::get('/projects', [PublicProjectController::class, 'index'])->name('projects.index');
Route::get('/project/{project:project_code}', [PublicProjectController::class, 'show'])->name('projects.show');
Route::get('/project/{project:project_code}/report-bug', [PublicBugController::class, 'create'])->name('bugs.create');
Route::post('/project/{project:project_code}/report-bug', [PublicBugController::class, 'store'])
    ->middleware('throttle:bug-reports')
    ->name('bugs.store');

Route::get('/bug/{bug:bug_code}', [PublicBugController::class, 'show'])->name('bugs.show');

/*
|--------------------------------------------------------------------------
| Google authentication (admins only)
|--------------------------------------------------------------------------
*/

Route::get('/login', [GoogleController::class, 'login'])->name('login');
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', [GoogleController::class, 'logout'])->middleware('auth')->name('logout');

// Local development sign-in without Google; see `php artisan admin:login-link`.
if (app()->environment('local', 'testing')) {
    // Signed relative to the host, so the link also works via a LAN IP instead of APP_URL.
    Route::get('/auth/login-link/{user}', LoginLinkController::class)
        ->middleware('signed:relative')
        ->name('auth.login-link');
}

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('projects', ProjectController::class);
        Route::post('projects/{project}/publish', [ProjectController::class, 'publish'])->name('projects.publish');
        Route::post('projects/{project}/close', [ProjectController::class, 'close'])->name('projects.close');
        Route::patch('projects/{project}/bug-reporting', [ProjectController::class, 'updateBugReporting'])->name('projects.bug-reporting');
        Route::post('projects/{project}/qr-code', [ProjectController::class, 'generateQrCode'])->name('projects.qr-code.generate');
        Route::get('projects/{project}/qr-code.{format}', [ProjectController::class, 'downloadQrCode'])
            ->whereIn('format', ['png', 'svg'])
            ->name('projects.qr-code.download');

        Route::get('bugs', [BugController::class, 'index'])->name('bugs.index');
        Route::get('bugs/{bug}', [BugController::class, 'show'])->name('bugs.show');
        Route::patch('bugs/{bug}', [BugController::class, 'update'])->name('bugs.update');
        Route::delete('bugs/{bug}', [BugController::class, 'destroy'])->name('bugs.destroy');
    });
