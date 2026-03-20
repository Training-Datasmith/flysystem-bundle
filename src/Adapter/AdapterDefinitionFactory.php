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
namespace League\Flysystem_Bundle\Adapter;

use League\Flysystem_Bundle\Adapter\Builder\Adapter_Definition_Builder_Interface;
use Symfony\Component\Dependency_Injection\Definition;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final readonly class Adapter_Definition_Factory
{
    /**
     * @var AdapterDefinitionBuilderInterface[]
     */
    private array $builders;
    public function __construct()
    {
        $this->builders = [new Builder\Async_Aws_Adapter_Definition_Builder(), new Builder\Aws_Adapter_Definition_Builder(), new Builder\Azure_Adapter_Definition_Builder(), new Builder\Ftp_Adapter_Definition_Builder(), new Builder\Gcloud_Adapter_Definition_Builder(), new Builder\Grid_Fs_Adapter_Definition_Builder(), new Builder\Local_Adapter_Definition_Builder(), new Builder\Memory_Adapter_Definition_Builder(), new Builder\Sftp_Adapter_Definition_Builder(), new Builder\Web_Dav_Adapter_Definition_Builder(), new Builder\Bunny_Cdn_Adapter_Definition_Builder()];
    }
    public function create_definition(string $name, array $options, ?string $default_visibility_for_directories = null): ?Definition
    {
        foreach ($this->builders as $builder) {
            if ($builder->get_name() === $name) {
                return $builder->create_definition($options, $default_visibility_for_directories);
            }
        }
        return null;
    }
}