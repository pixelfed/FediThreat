<?php

use Illuminate\Support\Str;

return [
    'admin_email' => env('FT_ADMIN_EMAIL', ''),
    'fediverse_acct' => env('FT_FEDI_ACCT', ''),

    'reports' => [
        'enabled' => env('FT_REPORTS_ENABLED', false),
    ]
];
