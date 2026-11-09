#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Composer\InstalledVersions;

$packages = [
    'league/flysystem-aws-s3-v3',
    'league/flysystem-ftp',
    'league/flysystem-google-cloud-storage',
    'league/flysystem-local',
    'league/flysystem-memory',
    'league/flysystem-read-only',
    'league/flysystem-sftp-v3',
    'psr/http-client',
    'psr/http-factory',
    'psr/http-message',
];

foreach ($packages as $package) {
    echo $package.'='.InstalledVersions::getReference($package).PHP_EOL;
}
