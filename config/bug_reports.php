<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Bug Report Rate Limit
    |--------------------------------------------------------------------------
    |
    | Accepted reports allowed per client IP within the window (default: 5 per
    | 10 minutes). Docker Desktop (Windows/macOS) shows every visitor with the
    | same gateway IP, so raise the limit when presenting from such a laptop.
    |
    */

    'rate_limit' => [
        'max_reports' => (int) env('BUG_REPORT_RATE_LIMIT', 5),
        'window_minutes' => (int) env('BUG_REPORT_RATE_LIMIT_MINUTES', 10),
    ],

];
