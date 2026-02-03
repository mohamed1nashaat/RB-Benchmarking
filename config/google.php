<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google services integration including Sheets API
    |
    */

    'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', storage_path('app/google/rb-benchmarks-92561a9f40e5.json')),

    'sheets' => [
        'application_name' => env('GOOGLE_SHEETS_APP_NAME', 'RB Benchmarks'),
        'scopes' => [
            'https://www.googleapis.com/auth/spreadsheets'
        ],
    ],

    'analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_MEASUREMENT_ID'),
        'api_secret' => env('GOOGLE_ANALYTICS_API_SECRET'),
    ],
];