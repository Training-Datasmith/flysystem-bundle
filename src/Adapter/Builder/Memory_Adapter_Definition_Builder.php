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

use League\Flysystem\In_Memory\In_Memory_Filesystem_Adapter;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Memory_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'memory';
    }
    protected function get_required_packages(): array
    {
        return [In_Memory_Filesystem_Adapter::class => 'league/flysystem-memory'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $definition->set_class(In_Memory_Filesystem_Adapter::class);
    }
}