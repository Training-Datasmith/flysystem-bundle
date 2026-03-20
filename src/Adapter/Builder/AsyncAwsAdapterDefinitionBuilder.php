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

use League\Flysystem\Async_Aws_S3\Async_Aws_S3adapter;
use League\Flysystem\Async_Aws_S3\Portable_Visibility_Converter;
use League\Flysystem\Visibility;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @internal
 */
final class Async_Aws_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'asyncaws';
    }
    protected function get_required_packages(): array
    {
        return [Async_Aws_S3adapter::class => 'league/flysystem-async-aws-s3'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('client');
        $resolver->set_allowed_types('client', 'string');
        $resolver->set_required('bucket');
        $resolver->set_allowed_types('bucket', 'string');
        $resolver->set_default('prefix', '');
        $resolver->set_allowed_types('prefix', 'string');
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $definition->set_class(Async_Aws_S3adapter::class);
        $definition->set_argument(0, new Reference($options['client']));
        $definition->set_argument(1, $options['bucket']);
        $definition->set_argument(2, $options['prefix']);
        $definition->set_argument(3, (new Definition(Portable_Visibility_Converter::class))->set_argument(0, $default_visibility_for_directories ?? Visibility::PUBLIC)->set_shared(false));
    }
}