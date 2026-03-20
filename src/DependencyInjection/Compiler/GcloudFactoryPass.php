<?php

declare (strict_types=1);
namespace League\Flysystem_Bundle\Dependency_Injection\Compiler;

use League\Flysystem\Google_Cloud_Storage\Google_Cloud_Storage_Adapter;
use League\Flysystem\Google_Cloud_Storage\Portable_Visibility_Handler;
use League\Flysystem\Google_Cloud_Storage\Uniform_Bucket_Level_Access_Visibility;
use Symfony\Component\Dependency_Injection\Compiler\Compiler_Pass_Interface;
use Symfony\Component\Dependency_Injection\Container_Builder;
/**
 * @internal
 */
final class Gcloud_Factory_Pass implements Compiler_Pass_Interface
{
    public function process(Container_Builder $container): void
    {
        if (!class_exists(Google_Cloud_Storage_Adapter::class)) {
            return;
        }
        $container->register(Portable_Visibility_Handler::class, Portable_Visibility_Handler::class);
        $container->set_alias('flysystem.adapter.gcloud.visibility.portable', Portable_Visibility_Handler::class);
        $container->register(Uniform_Bucket_Level_Access_Visibility::class, Uniform_Bucket_Level_Access_Visibility::class);
        $container->set_alias('flysystem.adapter.gcloud.visibility.uniform', Uniform_Bucket_Level_Access_Visibility::class);
    }
}