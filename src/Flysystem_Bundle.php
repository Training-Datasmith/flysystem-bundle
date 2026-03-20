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
namespace League\Flysystem_Bundle;

use League\Flysystem_Bundle\Dependency_Injection\Compiler\Gcloud_Factory_Pass;
use League\Flysystem_Bundle\Dependency_Injection\Compiler\Lazy_Factory_Pass;
use Symfony\Component\Dependency_Injection\Container_Builder;
use Symfony\Component\Http_Kernel\Bundle\Bundle;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
final class Flysystem_Bundle extends Bundle
{
    public function build(Container_Builder $container): void
    {
        parent::build($container);
        $container->add_compiler_pass(new Lazy_Factory_Pass());
        $container->add_compiler_pass(new Gcloud_Factory_Pass());
    }
}