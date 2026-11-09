<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('flysystem', [
        'storages' => [
            'cluster_local' => [
                'adapter' => 'local',
                'options' => [
                    'directory' => '%kernel.cache_dir%/cluster-local',
                ],
            ],
            'cluster_memory' => [
                'adapter' => 'memory',
            ],
            'cluster_lazy' => [
                'adapter' => 'lazy',
                'options' => [
                    'source' => 'cluster_memory',
                ],
            ],
            'cluster_local_ro' => [
                'adapter' => 'local',
                'read_only' => true,
                'options' => [
                    'directory' => '%kernel.cache_dir%/cluster-local',
                ],
            ],
            'cluster_ftp' => [
                'adapter' => 'ftp',
                'options' => [
                    'host' => '127.0.0.1',
                    'username' => 'flysystem',
                    'password' => 'flysystem',
                    'port' => 21,
                    'root' => '/',
                    'passive' => true,
                    'ssl' => false,
                    'timeout' => 30,
                    'ignore_passive_address' => true,
                ],
            ],
            'cluster_sftp' => [
                'adapter' => 'sftp',
                'options' => [
                    'host' => '127.0.0.1',
                    'username' => 'flysystem',
                    'password' => 'flysystem',
                    'port' => 2222,
                    'root' => '/upload',
                    'timeout' => 30,
                ],
            ],
            'cluster_aws' => [
                'adapter' => 'aws',
                'options' => [
                    'client' => 'cluster_aws_client',
                    'bucket' => 'flysystem-system-test',
                    'prefix' => 'bundle-cluster/',
                ],
            ],
            'cluster_gcloud' => [
                'adapter' => 'gcloud',
                'options' => [
                    'client' => 'cluster_gcloud_client',
                    'bucket' => 'flysystem-system-test',
                    'prefix' => 'bundle-cluster/',
                ],
            ],
        ],
    ]);
};
