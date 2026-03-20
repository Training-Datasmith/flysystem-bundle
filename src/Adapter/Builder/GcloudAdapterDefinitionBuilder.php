<?php

declare (strict_types=1);
/*
 * This file is part of the flysystem-bundle project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace League\Flysystem_Bundle\Adapter\Builder;

use League\Flysystem\Google_Cloud_Storage\Google_Cloud_Storage_Adapter;
use League\Flysystem\Visibility;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Gcloud_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'gcloud';
    }
    protected function get_required_packages(): array
    {
        return [Google_Cloud_Storage_Adapter::class => 'league/flysystem-google-cloud-storage'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('client');
        $resolver->set_allowed_types('client', 'string');
        $resolver->set_required('bucket');
        $resolver->set_allowed_types('bucket', 'string');
        $resolver->set_default('prefix', '');
        $resolver->set_allowed_types('prefix', 'string');
        $resolver->set_default('visibility_handler', null);
        $resolver->set_allowed_types('visibility_handler', ['string', 'null']);
        $resolver->set_default('streamReads', false);
        $resolver->set_allowed_types('streamReads', 'bool');
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $bucket_definition = new Definition();
        $bucket_definition->set_factory([new Reference($options['client']), 'bucket']);
        $bucket_definition->set_argument(0, $options['bucket']);
        $visibility_handler_reference = null;
        if (null !== $options['visibility_handler']) {
            $visibility_handler_reference = new Reference($options['visibility_handler']);
        }
        $definition->set_class(Google_Cloud_Storage_Adapter::class);
        $definition->set_argument(0, $bucket_definition);
        $definition->set_argument(1, $options['prefix']);
        $definition->set_argument(2, $visibility_handler_reference);
        $definition->set_argument(3, Visibility::PRIVATE);
        $definition->set_argument(4, null);
        $definition->set_argument(5, $options['streamReads']);
    }
}