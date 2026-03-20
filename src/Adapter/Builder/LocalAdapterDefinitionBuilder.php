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

use League\Flysystem\Local\Local_Filesystem_Adapter;
use League\Flysystem\Visibility;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Local_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'local';
    }
    protected function get_required_packages(): array
    {
        return [];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('directory');
        $resolver->set_allowed_types('directory', 'string');
        $this->configure_unix_options($resolver);
        $resolver->set_default('lock', 0);
        $resolver->set_allowed_types('lock', 'scalar');
        $resolver->set_default('skip_links', false);
        $resolver->set_allowed_types('skip_links', 'scalar');
        $resolver->set_default('lazy_root_creation', false);
        $resolver->set_allowed_types('lazy_root_creation', 'scalar');
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $definition->set_class(Local_Filesystem_Adapter::class);
        $definition->set_argument(0, $options['directory']);
        $definition->set_argument(1, $this->create_unix_definition($options['permissions'], $default_visibility_for_directories ?? Visibility::PRIVATE));
        $definition->set_argument(2, $options['lock']);
        $definition->set_argument(3, $options['skip_links'] ? Local_Filesystem_Adapter::SKIP_LINKS : Local_Filesystem_Adapter::DISALLOW_LINKS);
        $definition->set_argument(4, null);
        $definition->set_argument(5, $options['lazy_root_creation']);
    }
}