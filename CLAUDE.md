# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

A public bug-reporting app. An admin publishes a project and shares its QR code at a presentation. Anonymous visitors report bugs, and the admin triages them. [spec-simple-public-bug-reporting-laravel13-inertia-react-docker.md](spec-simple-public-bug-reporting-laravel13-inertia-react-docker.md), written in Thai and English, is the source of truth for scope. Do not build anything from its "Out of Scope" list (§51).

Stack: Laravel 13, PHP 8.4, Inertia.js **v3**, React 19, TypeScript 7, Tailwind 4, Vite 8, PostgreSQL 17, Socialite (Google), Docker Compose.

## Commands

Everything runs in containers. PHP, Composer and Node are not installed on the host.

```bash
docker compose up -d                                        # entrypoint: composer install, key:generate, storage:link, migrate
docker compose exec app php artisan db:seed                 # sample admin, projects (PORTFOLIO-AI, ...) and bugs
docker compose exec app php artisan test                    # full suite (Postgres DB bug_reporting_test)
docker compose exec app php artisan test --filter=BugPrivacyTest              # one class
docker compose exec app php artisan test --filter=test_admin_can_verify_a_bug # one method
docker compose exec app vendor/bin/pint                     # PHP style (use --test to check only)
docker compose run --rm node npm run types                  # tsc --noEmit (use `exec node` if the service is up)
docker compose run --rm node npm run build                  # production assets in public/build
docker compose exec app php artisan admin:login-link        # local-only signed admin login link (no Google needed)
```

## Docker specifics

- The Compose project is named `public-bug-reporting` (`name:` in `compose.yaml`). Another checkout on this machine, `D:\mylab-2026\antigravity\genbug-report`, uses the default name `genbug-report`. Never run compose commands that target that name.
- Host ports come from `.env`: `APP_PORT`, `FORWARD_DB_PORT` and `DEV_SERVER_PORT` (defaults 8080, 5432, 5173). This machine's `.env` uses 8081, 5433 and 5174 because the other project holds the defaults. Keep `APP_URL` and `GOOGLE_REDIRECT_URI` in sync with `APP_PORT`.
- `vendor/` is a named volume. The host `vendor/` is stale and serves only the IDE, so always run `composer` inside the `app` container. `node_modules/` is a named volume of the `node` service.
- The `node` service writes `public/hot` while running, and Vite deletes it on stop (`init: true` plus `exec`). If a stale `public/hot` is left behind, pages load no JavaScript.
- The test database `bug_reporting_test` is created by `docker/postgres/init/*.sql`, which only runs when the Postgres volume is first initialised.

## Architecture

- **Routes** ([routes/web.php](routes/web.php)):
  - Public pages bind by code: `/project/{project:project_code}`, `/bug/{bug:bug_code}`. `resolveRouteBinding` upper-cases the code, so URLs are case-insensitive.
  - The admin area is `/admin/*` behind the `auth` and `admin` middleware ([EnsureAdmin](app/Http/Middleware/EnsureAdmin.php)), with id binding.
  - Admin access is only the `ADMIN_EMAILS` allowlist ([config/admin.php](config/admin.php), `User::isAllowlisted`). There are no roles.
- **Privacy invariant:**
  - Public responses must never contain `reporter_name`, `reporter_email`, `reporter_ip` or `admin_note`.
  - Bugs reach public pages only through [PublicBugResource](app/Http/Resources/PublicBugResource.php), an explicit whitelist. [AdminBugResource](app/Http/Resources/AdminBugResource.php) extends it. The `Bug` model also `#[Hidden]`s those fields.
  - Screenshot `file_name` is admin-only, because it can identify the reporter.
  - Shared props ([HandleInertiaRequests](app/Http/Middleware/HandleInertiaRequests.php)) reach every page, so keep them minimal.
  - [BugPrivacyTest](tests/Feature/BugPrivacyTest.php) checks HTML, Inertia props, Inertia XHR JSON and `Accept: application/json` responses.
- **Status rules:**
  - `ProjectStatus`: Draft pages 404 for the public (admins can preview). Closed stays visible.
  - New reports need Published **and** the admin's per-project switch `bug_reporting_enabled` (default on; `PATCH /admin/projects/{id}/bug-reporting`, also in the project form). Always check `Project::acceptsBugReports()`, which the API exposes as `accepts_bug_reports`. Turning the switch off keeps the project and its board visible.
  - New bugs are always Pending and Open, and the public form cannot set admin fields.
  - `Bug::applyReview()` keeps verification and status consistent. Rejected or Duplicate verification forces the matching status, and leaving that state reopens the bug.
  - `bug_code` (`BUG-001`) is derived from the id in the `created` event.
- **Enums** live in [app/Enums](app/Enums) as lower-snake string values. [resources/js/lib/enums.ts](resources/js/lib/enums.ts) mirrors their labels, so update both together.
- **Filtering and stats:**
  - [BugFilters](app/Support/BugFilters.php) turns the query string (`search`, `status`, `severity`, `verification`, admin-only `project`, `priority`, `from`, `to`) into sanitized filters, dropping invalid values.
  - These feed the `search()` and `filter()` scopes on `Bug`. Public search covers code and title; admin search also covers description and reporter email.
  - [BugStats](app/Support/BugStats.php) builds dashboard counts and chart data using conditional aggregates.
- **Anti-spam:**
  - The `bug-reports` limiter lives in [AppServiceProvider](app/Providers/AppServiceProvider.php): 5 per 10 minutes per IP by default.
  - The limit is configurable in [config/bug_reports.php](config/bug_reports.php) (`BUG_REPORT_RATE_LIMIT`). Docker Desktop NATs all clients to the gateway IP, so every visitor shares one bucket there.
  - It counts only accepted reports, via `Limit::after()` plus a flag the controller sets on `request()`. Do not set the flag on the FormRequest, which holds its own copy of the request attributes.
  - The honeypot field `website` makes the controller fake success without saving anything.
- **QR codes:** [QrCodeService](app/Services/QrCodeService.php) (endroid/qr-code) writes `qr-codes/project-{id}.png|svg` to the public disk when a project is published or updated while public. It encodes `APP_URL` plus the project path.
- **Files:**
  - The public disk URL is relative (`/storage`), so images work from any host.
  - nginx also aliases `/storage/` directly.
  - Attachment and image files are deleted through model events using `DB::afterCommit`.
- **Audit log:** `AuditLog::record($admin, 'bug.updated', $bug, $metadata)` is used for every admin action. The frontend renders entries in [ActivityList.tsx](resources/js/Components/ActivityList.tsx).
- **Inertia v3 notes:**
  - Pages live in `resources/js/Pages` (config `inertia.pages.paths`), and SSR is disabled.
  - One-time messages use `Inertia::flash('success'|'error', ...)`, read on the client as `usePage().flash`.
  - `JsonResource::withoutWrapping()` is on, so a single resource is a plain object, while paginated collections are `{data, links, meta}`.
  - Uploads with PUT use POST plus `_method: 'put'` and `forceFormData`.
  - Friendly error pages come from `Inertia::handleExceptionsUsing`, rendering `Pages/Error.tsx`.
- **UI tokens** (ink, surface, status colors, chart `series-1`) are defined in [resources/css/app.css](resources/css/app.css). Badges show a colored dot next to ink-colored text, so meaning never relies on color alone. Charts are single-hue horizontal bar tables ([HorizontalBarChart.tsx](resources/js/Components/HorizontalBarChart.tsx)).
- Non-production environments enable `Model::preventLazyLoading()` and `preventSilentlyDiscardingAttributes()`. Eager-load relations and keep `#[Fillable]` lists complete.

## Testing notes

- Use `Storage::fake('public')` in tests that publish projects (QR codes) or upload files.
- `UploadedFile::fake()` reports its MIME type from the file name. To test content sniffing, build a real `UploadedFile($path, $name, null, null, true)`.
- `$this->inertiaGet($uri)` in [tests/TestCase.php](tests/TestCase.php) performs an Inertia XHR visit. `$this->admin()` creates an allowlisted admin.
