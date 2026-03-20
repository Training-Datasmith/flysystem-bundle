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

use Symfony\Component\Config\Definition\Builder\Tree_Builder;
use Symfony\Component\Config\Definition\Configuration_Interface;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Configuration implements Configuration_Interface
{
    public function get_config_tree_builder(): Tree_Builder
    {
        $tree_builder = new Tree_Builder('flysystem');
        $root_node = $tree_builder->get_root_node();
        $root_node->fix_xml_config('storage')->children()->array_node('storages')->use_attribute_as_key('name')->array_prototype()->perform_no_deep_merging()->children()->scalar_node('adapter')->is_required()->end()->array_node('options')->variable_prototype()->end()->default_value([])->end()->scalar_node('visibility')->default_null()->end()->scalar_node('directory_visibility')->default_null()->end()->boolean_node('retain_visibility')->default_null()->end()->boolean_node('case_sensitive')->default_true()->end()->boolean_node('disable_asserts')->default_false()->end()->array_node('public_url')->before_normalization()->cast_to_array()->end()->default_value([])->scalar_prototype()->end()->end()->scalar_node('path_normalizer')->default_null()->end()->scalar_node('public_url_generator')->default_null()->end()->scalar_node('temporary_url_generator')->default_null()->end()->boolean_node('read_only')->default_false()->end()->end()->end()->default_value([])->end()->end();
        return $tree_builder;
    }
}