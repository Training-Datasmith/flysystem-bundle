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

use Doctrine\ODM\Mongo_Db\Document_Manager;
use League\Flysystem\Grid_Fs\Grid_Fs_Adapter;
use Mongo_Db\Client;
use Mongo_Db\Grid_Fs\Bucket;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Exception\InvalidArgumentException;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class Grid_Fs_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'gridfs';
    }
    protected function get_required_packages(): array
    {
        return [Grid_Fs_Adapter::class => 'league/flysystem-gridfs'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->define('bucket')->default(null)->allowed_types('string', 'null');
        $resolver->define('prefix')->default('')->allowed_types('string');
        $resolver->define('database')->default(null)->allowed_types('string', 'null');
        $resolver->define('doctrine_connection')->allowed_types('string');
        $resolver->define('mongodb_uri')->allowed_types('string');
        $resolver->define('mongodb_uri_options')->default([])->allowed_types('array');
        $resolver->define('mongodb_driver_options')->default([])->allowed_types('array');
    }
    /**
     * @param array{bucket:string|null, prefix:string, database:string|null, doctrine_connection?:string, mongodb_uri?:string, mongodb_uri_options:array, mongodb_driver_options:array} $options
     */
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        if (isset($options['doctrine_connection'])) {
            if (isset($options['mongodb_uri'])) {
                throw new InvalidArgumentException('In GridFS configuration, "doctrine_connection" and "mongodb_uri" options cannot be set together.');
            }
            $bucket = new Definition(Bucket::class);
            $bucket->set_factory(self::initialize_bucket_from_document_manager(...));
            $bucket->set_arguments([new Reference(sprintf('doctrine_mongodb.odm.%s_document_manager', $options['doctrine_connection'])), $options['database'], $options['bucket']]);
        } elseif (isset($options['mongodb_uri'])) {
            $bucket = new Definition(Bucket::class);
            $bucket->set_factory(self::initialize_bucket_from_config(...));
            $bucket->set_arguments([$options['mongodb_uri'], $options['mongodb_uri_options'], $options['mongodb_driver_options'], $options['database'] ?? throw new InvalidArgumentException('MongoDB "database" name is required for Flysystem GridFS configuration'), $options['bucket']]);
        } elseif ($options['bucket']) {
            $bucket = new Reference($options['bucket']);
        } else {
            throw new InvalidArgumentException('Flysystem GridFS configuration requires a "bucket" service name, a "mongodb_uri" or a "doctrine_connection" name');
        }
        $definition->set_class(Grid_Fs_Adapter::class);
        $definition->set_argument(0, $bucket);
        $definition->set_argument(1, $options['prefix']);
    }
    public static function initialize_bucket_from_document_manager(Document_Manager $document_manager, ?string $db_name, ?string $bucket_name): Bucket
    {
        return $document_manager->get_client()->select_database($db_name ?? $document_manager->get_configuration()->get_default_db())->select_grid_fs_bucket(['bucketName' => $bucket_name ?? 'fs', 'disableMD5' => true]);
    }
    public static function initialize_bucket_from_config(string $uri, array $uri_options, array $driver_options, ?string $db_name, ?string $bucket_name): Bucket
    {
        return (new Client($uri, $uri_options, $driver_options))->select_database($db_name)->select_grid_fs_bucket(['bucketName' => $bucket_name ?? 'fs', 'disableMD5' => true]);
    }
}