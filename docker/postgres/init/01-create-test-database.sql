-- Separate database for `php artisan test` so the test suite never touches development data.
-- Runs only when the postgres volume is initialised for the first time.
CREATE DATABASE bug_reporting_test OWNER laravel ENCODING 'UTF8';
