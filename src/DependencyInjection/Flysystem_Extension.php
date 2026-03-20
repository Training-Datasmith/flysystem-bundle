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
namespace League\Flysystem_Bundle\Dependency_Injection;

use League\Flysystem\Filesystem;
use League\Flysystem\Filesystem_Operator;
use League\Flysystem\Filesystem_Reader;
use League\Flysystem\Filesystem_Writer;
use League\Flysystem\Read_Only\Read_Only_Filesystem_Adapter;
use League\Flysystem_Bundle\Adapter\Adapter_Definition_Factory;
use League\Flysystem_Bundle\Exception\Missing_Package_Exception;
use League\Flysystem_Bundle\Lazy\Lazy_Factory;
use Symfony\Component\Dependency_Injection\Container_Builder;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Extension\Extension;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
final class Flysystem_Extension extends Extension
{
    public function load(array $configs, Container_Builder $container): void
    {
        $configuration = new Configuration();
        $config = $this->process_configuration($configuration, $configs);
        $container->set_definition('flysystem.adapter.lazy.factory', new Definition(Lazy_Factory::class))->set_public(false);
        $this->create_storages_definitions($config, $container);
    }
    private function create_storages_definitions(array $config, Container_Builder $container): void
    {
        $definition_factory = new Adapter_Definition_Factory();
        foreach ($config['storages'] as $storage_name => $storage_config) {
            // If the storage is a lazy one, it's resolved at runtime
            if ('lazy' === $storage_config['adapter']) {
                $container->set_definition($storage_name, $this->create_lazy_storage_definition($storage_name, $storage_config['options']));
                // Register named autowiring alias
                $container->register_alias_for_argument($storage_name, Filesystem_Operator::class, $storage_name)->set_public(false);
                $container->register_alias_for_argument($storage_name, Filesystem_Reader::class, $storage_name)->set_public(false);
                $container->register_alias_for_argument($storage_name, Filesystem_Writer::class, $storage_name)->set_public(false);
                continue;
            }
            // Create adapter definition
            if ($adapter = $definition_factory->create_definition($storage_config['adapter'], $storage_config['options'], $storage_config['directory_visibility'] ?? null)) {
                // Native adapter
                $container->set_definition($id = 'flysystem.adapter.' . $storage_name, $adapter)->set_public(false);
            } else {
                // Custom adapter
                $container->set_alias($id = 'flysystem.adapter.' . $storage_name, $storage_config['adapter'])->set_public(false);
            }
            // Create ReadOnly adapter
            if ($storage_config['read_only']) {
                if (!class_exists(Read_Only_Filesystem_Adapter::class)) {
                    throw new Missing_Package_Exception("Missing package, to use the readonly option, run:\n\ncomposer require league/flysystem-read-only");
                }
                $original_adapter_id = $id;
                $container->set_definition($id = $id . '.read_only', $this->create_read_only_adapter_definition(new Reference($original_adapter_id)));
            }
            // Create storage definition
            $container->set_definition($storage_name, $this->create_storage_definition($storage_name, new Reference($id), $storage_config));
            // Register named autowiring alias
            $container->register_alias_for_argument($storage_name, Filesystem_Operator::class, $storage_name)->set_public(false);
            $container->register_alias_for_argument($storage_name, Filesystem_Reader::class, $storage_name)->set_public(false);
            $container->register_alias_for_argument($storage_name, Filesystem_Writer::class, $storage_name)->set_public(false);
        }
    }
    private function create_lazy_storage_definition(string $storage_name, array $options): Definition
    {
        $resolver = new Options_Resolver();
        $resolver->set_required('source');
        $resolver->set_allowed_types('source', 'string');
        $definition = new Definition(Filesystem_Operator::class);
        $definition->set_public(false);
        $definition->set_factory([new Reference('flysystem.adapter.lazy.factory'), 'createStorage']);
        $definition->set_argument(0, $resolver->resolve($options)['source']);
        $definition->set_argument(1, $storage_name);
        $definition->add_tag('flysystem.storage', ['storage' => $storage_name]);
        return $definition;
    }
    private function create_storage_definition(string $storage_name, Reference $adapter, array $config): Definition
    {
        $public_url = null;
        if ($config['public_url']) {
            $public_url = 1 === count($config['public_url']) ? $config['public_url'][0] : $config['public_url'];
        }
        $definition = new Definition(Filesystem::class);
        $definition->set_public(false);
        $definition->set_argument(0, $adapter);
        $definition->set_argument(1, ['visibility' => $config['visibility'], 'directory_visibility' => $config['directory_visibility'], 'retain_visibility' => $config['retain_visibility'], 'case_sensitive' => $config['case_sensitive'], 'disable_asserts' => $config['disable_asserts'], 'public_url' => $public_url]);
        $definition->set_argument(2, $config['path_normalizer'] ? new Reference($config['path_normalizer']) : null);
        $definition->set_argument(3, $config['public_url_generator'] ? new Reference($config['public_url_generator']) : null);
        $definition->set_argument(4, $config['temporary_url_generator'] ? new Reference($config['temporary_url_generator']) : null);
        $definition->add_tag('flysystem.storage', ['storage' => $storage_name]);
        return $definition;
    }
    private function create_read_only_adapter_definition(Reference $adapter): Definition
    {
        $definition = new Definition(Read_Only_Filesystem_Adapter::class);
        $definition->set_public(false);
        $definition->set_argument(0, $adapter);
        return $definition;
    }
}