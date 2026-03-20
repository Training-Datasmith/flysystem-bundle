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

use Platform_Community\Flysystem\Bunny_Cdn\Bunny_Cdn_Adapter;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @internal
 */
final class Bunny_Cdn_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'bunnycdn';
    }
    protected function get_required_packages(): array
    {
        return [Bunny_Cdn_Adapter::class => 'platformcommunity/flysystem-bunnycdn'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('client');
        $resolver->set_allowed_types('client', 'string');
        $resolver->set_default('pull_zone', '');
        $resolver->set_allowed_types('pull_zone', 'string');
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $definition->set_class(Bunny_Cdn_Adapter::class);
        $definition->set_arguments([new Reference($options['client']), $options['pull_zone']]);
    }
}