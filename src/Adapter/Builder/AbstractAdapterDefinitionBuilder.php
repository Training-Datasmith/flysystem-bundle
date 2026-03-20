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

use League\Flysystem\Unix_Visibility\Portable_Visibility_Converter;
use League\Flysystem_Bundle\Exception\Missing_Package_Exception;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
abstract class Abstract_Adapter_Definition_Builder implements Adapter_Definition_Builder_Interface
{
    final public function create_definition(array $options, ?string $default_visibility_for_directories): Definition
    {
        $this->ensure_required_packages_available();
        $resolver = new Options_Resolver();
        $this->configure_options($resolver);
        $definition = new Definition();
        $definition->set_public(false);
        $this->configure_definition($definition, $resolver->resolve($options), $default_visibility_for_directories);
        return $definition;
    }
    abstract protected function get_required_packages(): array;
    abstract protected function configure_options(Options_Resolver $resolver);
    abstract protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories);
    protected function configure_unix_options(Options_Resolver $resolver): void
    {
        $method = method_exists($resolver, 'setOptions') ? 'setOptions' : 'setDefault';
        $resolver->{$method}('permissions', function (Options_Resolver $sub_resolver) use ($method): void {
            $sub_resolver->{$method}('file', function (Options_Resolver $perms_resolver): void {
                $perms_resolver->set_default('public', 0644);
                $perms_resolver->set_allowed_types('public', 'scalar');
                $perms_resolver->set_default('private', 0600);
                $perms_resolver->set_allowed_types('private', 'scalar');
            });
            $sub_resolver->{$method}('dir', function (Options_Resolver $perms_resolver): void {
                $perms_resolver->set_default('public', 0755);
                $perms_resolver->set_allowed_types('public', 'scalar');
                $perms_resolver->set_default('private', 0700);
                $perms_resolver->set_allowed_types('private', 'scalar');
            });
        });
    }
    protected function create_unix_definition(array $permissions, string $default_visibility_for_directories): Definition
    {
        return (new Definition(Portable_Visibility_Converter::class))->set_factory([Portable_Visibility_Converter::class, 'fromArray'])->add_argument(['file' => ['public' => (int) $permissions['file']['public'], 'private' => (int) $permissions['file']['private']], 'dir' => ['public' => (int) $permissions['dir']['public'], 'private' => (int) $permissions['dir']['private']]])->add_argument($default_visibility_for_directories)->set_shared(false);
    }
    private function ensure_required_packages_available(): void
    {
        $missing_packages = [];
        foreach ($this->get_required_packages() as $required_class => $package_name) {
            if (!class_exists($required_class)) {
                $missing_packages[] = $package_name;
            }
        }
        if (!$missing_packages) {
            return;
        }
        throw new Missing_Package_Exception(sprintf("Missing package%s, to use the \"%s\" adapter, run:\n\ncomposer require %s", \count($missing_packages) > 1 ? 's' : '', $this->get_name(), implode(' ', $missing_packages)));
    }
}