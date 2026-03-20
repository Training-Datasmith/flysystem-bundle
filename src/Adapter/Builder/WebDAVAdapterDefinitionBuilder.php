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

use League\Flysystem\Web_Dav\Web_Dav_Adapter;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 *
 * @internal
 */
final class Web_Dav_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'webdav';
    }
    protected function get_required_packages(): array
    {
        return [Web_Dav_Adapter::class => 'league/flysystem-webdav'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('client');
        $resolver->set_allowed_types('client', 'string');
        $resolver->set_default('prefix', '');
        $resolver->set_allowed_types('prefix', 'string');
        $resolver->set_default('visibility_handling', Web_Dav_Adapter::ON_VISIBILITY_THROW_ERROR);
        $resolver->set_allowed_types('visibility_handling', ['string']);
        $resolver->set_default('manual_copy', false);
        $resolver->set_allowed_types('manual_copy', 'bool');
        $resolver->set_default('manual_move', false);
        $resolver->set_allowed_types('manual_move', 'bool');
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $definition->set_class(Web_Dav_Adapter::class);
        $definition->set_arguments([new Reference($options['client']), $options['prefix'], $options['visibility_handling'], $options['manual_copy'], $options['manual_move']]);
    }
}