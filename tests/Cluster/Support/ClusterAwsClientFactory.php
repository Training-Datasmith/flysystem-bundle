<?php

/*
 * This file is part of the flysystem-bundle project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\League\FlysystemBundle\Cluster\Support;

use Aws\Middleware;
use Aws\S3\S3Client;
use Psr\Http\Message\RequestInterface;

final class ClusterAwsClientFactory
{
    private static ?ClusterAwsRequestRecorder $recorder = null;

    public static function create(): S3Client
    {
        self::$recorder ??= new ClusterAwsRequestRecorder();

        $client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => 'http://s3:9090',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => 'foo',
                'secret' => 'bar',
            ],
        ]);

        $recorder = self::$recorder;
        $client->getHandlerList()->appendSign(
            Middleware::mapRequest(static function (RequestInterface $request) use ($recorder): RequestInterface {
                $recorder->record($request);

                return $request;
            }),
            'cluster-test-request-recorder'
        );

        return $client;
    }

    public static function recorder(): ClusterAwsRequestRecorder
    {
        self::$recorder ??= new ClusterAwsRequestRecorder();

        return self::$recorder;
    }
}
