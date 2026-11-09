<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Functional\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\PdoBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the PDO cache backend
 *
 * These tests use actual database servers and will place and remove keys in the db!
 * Since all keys have the 'TestCache:' prefix, running the tests should have
 * no side effects on non-related cache entries.
 */
#[RequiresPhpExtension('pdo')]
final class PdoBackendTest extends BaseTestCase
{
    /**
     * @var PdoBackend[]
     */
    private array $backends = [];

    private FrontendInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(FrontendInterface::class);
        $this->cache->method('getIdentifier')->willReturn('TestCache');
        $this->setupBackends();
    }

    protected function tearDown(): void
    {
        foreach ($this->backends as $backend) {
            $backend->flush();
        }
    }

    #[Test]
    public function setAddsCacheEntry(): void
    {
        foreach ($this->backends as $backend) {
            $backend->flush();

            // use data that contains binary junk
            $data = random_bytes(2048);
            $backend->set('some_entry', $data);
            self::assertEquals($data, $backend->get('some_entry'));
        }
    }

    #[Test]
    public function cacheEntriesCanBeIterated(): void
    {
        foreach ($this->backends as $backend) {
            $backend->flush();

            // use data that contains binary junk
            $data = random_bytes(128);
            $backend->set('first_entry', $data);
            $backend->set('second_entry', $data);
            $backend->set('third_entry', $data);

            $entries = 0;
            foreach ($backend as $entry) {
                self::assertEquals($data, $entry);
                $entries++;
            }

            self::assertSame(3, $entries);
        }
    }

    private function setupBackends(): void
    {
        try {
            $backend = new PdoBackend(
                new EnvironmentConfiguration('PdoBackend a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
                [
                    'dataSourceName' => 'sqlite::memory:',
                    'defaultLifetime' => 0
                ]
            );
            $backend->setup();
            $backend->setCache($this->cache);
            $backend->flush();
            $this->backends['sqlite'] = $backend;
        } catch (\Throwable) {
        }

        try {
            $backend = new PdoBackend(
                new EnvironmentConfiguration('PdoBackend a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
                [
                    'dataSourceName' => 'mysql:host=127.0.0.1;dbname=flow_functional_testing',
                    'username' => 'neos',
                    'password' => 'neos',
                    'defaultLifetime' => 0
                ]
            );
            $backend->setup();
            $backend->setCache($this->cache);
            $backend->flush();
            $this->backends['mysql'] = $backend;
        } catch (\Throwable) {
        }

        try {
            $backend = new PdoBackend(
                new EnvironmentConfiguration('PdoBackend a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
                [
                    'dataSourceName' => 'pgsql:host=127.0.0.1;dbname=flow_functional_testing',
                    'username' => 'neos',
                    'password' => 'neos',
                    'defaultLifetime' => 0
                ]
            );
            $backend->setup();
            $backend->setCache($this->cache);
            $backend->flush();
            $this->backends['pgsql'] = $backend;
        } catch (\Throwable) {
        }
    }
}
