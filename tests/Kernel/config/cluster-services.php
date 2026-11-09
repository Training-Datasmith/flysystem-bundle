<?php

use Aws\S3\S3Client;
use Google\Cloud\Storage\StorageClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\League\FlysystemBundle\Cluster\Support\ClusterAwsClientFactory;
use Tests\League\FlysystemBundle\Cluster\Support\ClusterGcloudClientFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()
        ->public();

    $services->set('cluster_aws_client', S3Client::class)
        ->factory([ClusterAwsClientFactory::class, 'create']);

    $services->set('cluster_gcloud_client', StorageClient::class)
        ->factory([ClusterGcloudClientFactory::class, 'create']);

    $services->alias('flysystem.test.cluster_local', 'cluster_local');
    $services->alias('flysystem.test.cluster_memory', 'cluster_memory');
    $services->alias('flysystem.test.cluster_lazy', 'cluster_lazy');
    $services->alias('flysystem.test.cluster_local_ro', 'cluster_local_ro');
    $services->alias('flysystem.test.cluster_ftp', 'cluster_ftp');
    $services->alias('flysystem.test.cluster_sftp', 'cluster_sftp');
    $services->alias('flysystem.test.cluster_aws', 'cluster_aws');
    $services->alias('flysystem.test.cluster_gcloud', 'cluster_gcloud');
};
