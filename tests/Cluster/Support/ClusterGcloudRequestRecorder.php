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

use Psr\Http\Message\RequestInterface;

final class ClusterGcloudRequestRecorder
{
    private ?RequestInterface $lastRequest = null;

    public function record(RequestInterface $request): void
    {
        $this->lastRequest = $request;
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }

    public function reset(): void
    {
        $this->lastRequest = null;
    }
}
