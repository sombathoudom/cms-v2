<?php

return [
    'enabled' => env('SCOUT_ENABLED', false),

    'driver' => env('SCOUT_DRIVER', 'database'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'soft_delete' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'directories' => [
        app_path(),
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
    ],
];
