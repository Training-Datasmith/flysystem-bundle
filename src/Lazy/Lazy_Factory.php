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
namespace League\Flysystem_Bundle\Lazy;

use League\Flysystem\Filesystem_Operator;
use Psr\Container\Container_Interface;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final readonly class Lazy_Factory
{
    public function __construct(private Container_Interface $storages)
    {
    }
    public function create_storage(string $source, string $storage_name): Filesystem_Operator
    {
        if ($source === $storage_name) {
            throw new \InvalidArgumentException('The "lazy" adapter source is referring to itself as "' . $source . '", which would lead to infinite recursion.');
        }
        if (!$this->storages->has($source)) {
            throw new \InvalidArgumentException('You have requested a non-existent source storage "' . $source . '" in lazy storage "' . $storage_name . '".');
        }
        return $this->storages->get($source);
    }
}