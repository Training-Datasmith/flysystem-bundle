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
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestInterface;

final class ClusterGcloudClientFactory
{
    private static ?ClusterGcloudHttpRecorder $recorder = null;

    public static function create(): StorageClient
    {
        $recorder = self::recorder();

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(static function (RequestInterface $request) use ($recorder): RequestInterface {
            $recorder->createRequest($request->getMethod(), (string) $request->getUri());

            return $request;
        }));
        $stack->push(Middleware::tap(null, static function () use ($recorder): void {
            ++$recorder->sendRequestCalls;
        }));

        $guzzle = new Client([
            'handler' => $stack,
            'base_uri' => 'http://127.0.0.1:4443',
        ]);

        $httpHandler = HttpHandlerFactory::build($guzzle);

        return new StorageClient([
            'projectId' => 'test',
            'apiEndpoint' => 'http://127.0.0.1:4443',
            'authHttpHandler' => $httpHandler,
            'httpHandler' => $httpHandler,
        ]);
    }

    public static function recorder(): ClusterGcloudHttpRecorder
    {
        self::$recorder ??= new ClusterGcloudHttpRecorder(
            new Client(),
            new HttpFactory(),
        );

        return self::$recorder;
    }
}
