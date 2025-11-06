<?php

return [
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_mixed_case' => env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_letters' => env('PASSWORD_REQUIRE_LETTERS', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'uncompromised' => env('PASSWORD_REQUIRE_UNCOMPROMISED', true),
        'reuse_prevent' => env('PASSWORD_PREVENT_REUSE', 5),
    ],
    'session' => [
        'idle_timeout' => env('SESSION_IDLE_TIMEOUT', 900), // seconds
    ],
];
