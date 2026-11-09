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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ClusterGcloudHttpRecorder implements ClientInterface, RequestFactoryInterface
{
    public int $createRequestCalls = 0;

    public int $sendRequestCalls = 0;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
    ) {
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        ++$this->createRequestCalls;

        return $this->requestFactory->createRequest($method, $uri);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        ++$this->sendRequestCalls;

        return $this->client->sendRequest($request);
    }
}
