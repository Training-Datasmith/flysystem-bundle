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

use League\Flysystem\Azure_Blob_Storage\Azure_Blob_Storage_Adapter;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Azure_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'azure';
    }
    protected function get_required_packages(): array
    {
        return [Azure_Blob_Storage_Adapter::class => 'league/flysystem-azure-blob-storage'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('client');
        $resolver->set_allowed_types('client', 'string');
        $resolver->set_required('container');
        $resolver->set_allowed_types('container', 'string');
        $resolver->set_default('prefix', '');
        $resolver->set_allowed_types('prefix', 'string');
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $definition->set_class(Azure_Blob_Storage_Adapter::class);
        $definition->set_argument(0, new Reference($options['client']));
        $definition->set_argument(1, $options['container']);
        $definition->set_argument(2, $options['prefix']);
    }
}