<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Email Allowlist
    |--------------------------------------------------------------------------
    |
    | Only Google accounts whose email is listed here may sign in and use the
    | admin area. Set ADMIN_EMAILS in .env as a comma-separated list.
    |
    */

    'emails' => array_values(array_filter(array_map(
        fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
