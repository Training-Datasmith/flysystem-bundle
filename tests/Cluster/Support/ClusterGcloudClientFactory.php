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

use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

final class ClusterGcloudClientFactory
{
    private static ?ClusterGcloudRequestRecorder $recorder = null;

    public static function create(): StorageClient
    {
        $recorder = self::recorder();

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(
            static function (RequestInterface $request) use ($recorder): RequestInterface {
                $recorder->record($request);

                return $request;
            }
        ));

        $guzzle = new Client([
            'handler' => $stack,
            'base_uri' => 'http://gcs:4443',
        ]);

        $httpHandler = HttpHandlerFactory::build($guzzle);

        return new StorageClient([
            'projectId' => 'test',
            'apiEndpoint' => 'http://gcs:4443',
            'authHttpHandler' => $httpHandler,
            'httpHandler' => $httpHandler,
        ]);
    }

    public static function recorder(): ClusterGcloudRequestRecorder
    {
        self::$recorder ??= new ClusterGcloudRequestRecorder();

        return self::$recorder;
    }
}
