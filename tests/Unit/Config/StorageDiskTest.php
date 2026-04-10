<?php

declare(strict_types=1);

it('uses FILESYSTEM_DISK for media library storage when configured', function () {
    $_ENV['FILESYSTEM_DISK'] = 's3';
    putenv('FILESYSTEM_DISK=s3');

    $config = require config_path('media-library.php');

    expect($config['disk_name'])->toBe('s3');
});
