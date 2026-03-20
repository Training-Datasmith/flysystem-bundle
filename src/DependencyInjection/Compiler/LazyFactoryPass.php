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
namespace League\Flysystem_Bundle\Dependency_Injection\Compiler;

use Symfony\Component\Dependency_Injection\Compiler\Compiler_Pass_Interface;
use Symfony\Component\Dependency_Injection\Compiler\Service_Locator_Tag_Pass;
use Symfony\Component\Dependency_Injection\Container_Builder;
use Symfony\Component\Dependency_Injection\Reference;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Lazy_Factory_Pass implements Compiler_Pass_Interface
{
    public function process(Container_Builder $container): void
    {
        $factories = [];
        foreach ($container->find_tagged_service_ids('flysystem.storage') as $service_id => $tags) {
            foreach ($tags as $tag) {
                if (isset($tag['storage'])) {
                    $factories[$tag['storage']] = new Reference($service_id);
                }
            }
        }
        $lazy_factory = $container->get_definition('flysystem.adapter.lazy.factory');
        $lazy_factory->set_argument(0, Service_Locator_Tag_Pass::register($container, $factories));
    }
}