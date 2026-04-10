<?php

$config = require base_path('vendor/spatie/laravel-medialibrary/config/media-library.php');

$config['disk_name'] =
    $_ENV['FILESYSTEM_DISK']
    ?? $_SERVER['FILESYSTEM_DISK']
    ?? getenv('FILESYSTEM_DISK')
    ?: env('MEDIA_DISK', 'local');

return $config;
