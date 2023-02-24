<?php

return [
    'meeting_reminder_days' => 28,
    'project_db_api_url' => env('PROJECT_DB_API_URL', 'https://example.com/api/v1/students'),
    'api_key' => env('TIER4_API_KEY', \Illuminate\Support\Str::random(64)),
];
