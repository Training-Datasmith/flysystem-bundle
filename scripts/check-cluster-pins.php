#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Composer\InstalledVersions;

$expected = [
    'league/flysystem-aws-s3-v3' => '5b19097b66bbdc7254edd9f97c68783fb59c5aaf',
    'league/flysystem-ftp' => 'b715e9daa6308200c35244e624a5cb2af927d28f',
    'league/flysystem-google-cloud-storage' => '716c3c194850b6921c8e8ba6c65080240837c15d',
    'league/flysystem-local' => '5419f62dee53d275dc1741584f8d2b2a32389dd9',
    'league/flysystem-memory' => '7409cf151b3f4bb3903dc2910440843a73447521',
    'league/flysystem-read-only' => '679ed13bbb8892f76de8df675403951bdf24e903',
    'league/flysystem-sftp-v3' => '298b347a36be6e40cf43c2167549bef508e8ab5d',
    'psr/http-client' => 'd251c92d20c7a883c2cc9c7f0e467d15c3c2a795',
    'psr/http-factory' => 'f9a0862677e7b1b9bd8dc2ed459f48c7e06ff277',
    'psr/http-message' => 'c941fca6736161fe07e79fa83d85217b39411cef',
];

$failed = false;

foreach ($expected as $package => $sha) {
    if (!InstalledVersions::isInstalled($package)) {
        fwrite(STDERR, sprintf("Cluster pin check failed: %s is not installed.\n", $package));
        $failed = true;
        continue;
    }

    $reference = InstalledVersions::getReference($package);
    if (null === $reference) {
        fwrite(STDERR, sprintf("Cluster pin check failed: %s has no install reference.\n", $package));
        $failed = true;
        continue;
    }

    if (0 !== strcasecmp($reference, $sha)) {
        fwrite(STDERR, sprintf(
            "Cluster pin check failed: %s reference is %s, expected %s.\n",
            $package,
            $reference,
            $sha
        ));
        $failed = true;
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "Cluster pin check passed for ".count($expected)." packages.\n");

exit(0);
