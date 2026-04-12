<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limits for API endpoints (requests per minute).
    |
    */

    'rate_limits' => [
        'api_user' => env('API_RATE_LIMIT_USER', 60),
        'api_login' => env('API_RATE_LIMIT_LOGIN', 5),
        'api_refresh' => env('API_RATE_LIMIT_REFRESH', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Error Code Map
    |--------------------------------------------------------------------------
    |
    | Machine-readable error codes returned in JSON responses.
    |
    */

    'error_codes' => [
        'unauthorized' => 'UNAUTHORIZED',
        'not_authorized' => 'NOT_AUTHORIZED',
        'role_not_allowed' => 'ROLE_NOT_ALLOWED',
        'validation_error' => 'VALIDATION_ERROR',
        'not_found' => 'NOT_FOUND',
        'rate_limit_exceeded' => 'RATE_LIMIT_EXCEEDED',
        'assignment_required' => 'ASSIGNMENT_REQUIRED',
        'invalid_status' => 'INVALID_STATUS',
        'invalid_transition' => 'INVALID_TRANSITION',
    ],

];
