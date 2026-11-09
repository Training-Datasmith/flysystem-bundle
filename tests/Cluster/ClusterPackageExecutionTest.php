<?php

/*
 * This file is part of the flysystem-bundle project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\League\FlysystemBundle\Cluster;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Tests\League\FlysystemBundle\Cluster\Support\ClusterAwsClientFactory;
use Tests\League\FlysystemBundle\Cluster\Support\ClusterGcloudClientFactory;
use Tests\League\FlysystemBundle\Kernel\ClusterAppKernel;

class ClusterPackageExecutionTest extends TestCase
{
    private ClusterAppKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new ClusterAppKernel('test', true);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testClusterLocalStorageExecutesLocalAdapter(): void
    {
        $path = 'local/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-local-'.bin2hex(random_bytes(4));

        $fs = $this->filesystem('cluster_local');
        $fs->write($path, $contents);
        self::assertSame($contents, $fs->read($path));
        self::assertTrue($fs->fileExists($path));

        $listed = iterator_to_array($fs->listContents('local', false));
        self::assertNotEmpty($listed);

        $fs->delete($path);
        self::assertFalse($fs->fileExists($path));
    }

    public function testClusterMemoryStorageExecutesMemoryAdapter(): void
    {
        $path = 'memory/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-memory';

        $fs = $this->filesystem('cluster_memory');
        $fs->write($path, $contents);
        self::assertSame($contents, $fs->read($path));
        $fs->delete($path);
    }

    public function testClusterLazyStorageResolvesMemoryBackend(): void
    {
        $path = 'lazy/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-lazy';

        $lazy = $this->filesystem('cluster_lazy');
        $memory = $this->filesystem('cluster_memory');

        $lazy->write($path, $contents);
        self::assertSame($contents, $memory->read($path));

        $lazy->delete($path);
    }

    public function testClusterReadOnlyStorageExecutesReadOnlyAdapter(): void
    {
        $path = 'readonly/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-readonly-seed';

        $writer = $this->filesystem('cluster_local');
        $writer->write($path, $contents);

        $reader = $this->filesystem('cluster_local_ro');
        self::assertSame($contents, $reader->read($path));

        $this->expectException(UnableToWriteFile::class);
        $this->expectExceptionMessage('Unable to write file at location: '.$path.'. This is a readonly adapter.');

        $reader->write($path, 'blocked');
    }

    public function testClusterFtpStorageExecutesFtpAdapter(): void
    {
        $path = 'ftp/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-ftp';

        $fs = $this->filesystem('cluster_ftp');
        $fs->write($path, $contents);
        self::assertSame($contents, $fs->read($path));
        self::assertNotEmpty(iterator_to_array($fs->listContents('ftp', false)));
        $fs->delete($path);
    }

    public function testClusterSftpStorageExecutesSftpAdapter(): void
    {
        $path = 'sftp/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-sftp';

        $fs = $this->filesystem('cluster_sftp');
        $fs->write($path, $contents);
        self::assertSame($contents, $fs->read($path));
        self::assertNotEmpty(iterator_to_array($fs->listContents('sftp', false)));
        $fs->delete($path);
    }

    public function testClusterAwsStorageExecutesAwsAdapterAndPsrRequest(): void
    {
        $path = 'aws/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-aws';

        $recorder = ClusterAwsClientFactory::recorder();
        $recorder->reset();

        $fs = $this->filesystem('cluster_aws');
        $fs->write($path, $contents);
        self::assertSame($contents, $fs->read($path));
        $fs->delete($path);

        $request = $recorder->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('s3', $request->getUri()->getHost());
    }

    public function testClusterGcloudStorageExecutesGcloudAdapterAndPsr7Request(): void
    {
        $path = 'gcloud/'.bin2hex(random_bytes(8)).'.txt';
        $contents = 'cluster-gcloud';

        $recorder = ClusterGcloudClientFactory::recorder();
        $recorder->reset();

        $fs = $this->filesystem('cluster_gcloud');
        $fs->write($path, $contents);
        self::assertSame($contents, $fs->read($path));
        $fs->delete($path);

        $request = $recorder->getLastRequest();
        self::assertNotNull($request);
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('gcs', $request->getUri()->getHost());
    }

    private function filesystem(string $storageName): FilesystemOperator
    {
        return $this->kernel->getContainer()->get('flysystem.test.'.$storageName);
    }
}
