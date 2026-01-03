<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Download Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum connections per file for aria2c
    'max_connections' => env('DOWNLOAD_MAX_CONNECTIONS', 16),

    // Number of splits for aria2c
    'split_count' => env('DOWNLOAD_SPLIT_COUNT', 16),

    // Maximum simultaneous downloads
    'max_simultaneous' => env('DOWNLOAD_MAX_SIMULTANEOUS', 5),

    // Storage path relative to storage/app
    'storage_path' => env('DOWNLOAD_STORAGE_PATH', 'downloads'),
];
