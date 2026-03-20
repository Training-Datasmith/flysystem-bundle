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

use Symfony\Component\Dependency_Injection\Definition;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
interface Adapter_Definition_Builder_Interface
{
    public function get_name(): string;
    /**
     * Create the definition for this builder's adapter given an array of options.
     */
    public function create_definition(array $options, ?string $default_visibility_for_directories): Definition;
}