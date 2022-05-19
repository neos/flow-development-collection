<?php
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
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * Testcase for the PDO cache backend
 *
 * These tests use actual database servers and will place and remove keys in the db!
 * Since all keys have the 'TestCache:' prefix, running the tests should have
 * no side effects on non-related cache entries.
 *
 * @requires extension pdo
 */
class PdoBackendTest extends BaseTestCase
{
    /**
     * @var PdoBackend[]
     */
    private $backends = [];

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FrontendInterface
     */
    private $cache;

    protected function tearDown(): void
    {
        foreach ($this->backends as $backend) {
            $backend->flush();
        }
    }

    public function backendsToTest(): array
    {
        $this->cache = $this->createMock(FrontendInterface::class);
        $this->cache->method('getIdentifier')->willReturn('TestCache');
        $this->setupBackends();
        return $this->backends;
    }

    /**
     * @test
     * @dataProvider backendsToTest
     */
    public function setAddsCacheEntry($backend)
    {
        // use data that contains binary junk
        $data = random_bytes(2048);
        $backend->set('some_entry', $data);
        self::assertEquals($data, $backend->get('some_entry'));
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
            $this->backends['sqlite'] = [$backend];
        } catch (\Throwable $t) {
            $this->addWarning('SQLite DB is not reachable: ' . $t->getMessage());
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
            $this->backends['mysql'] = [$backend];
        } catch (\Throwable $t) {
            $this->addWarning('MySQL DB server is not reachable: ' . $t->getMessage());
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
            $this->backends['pgsql'] = [$backend];
        } catch (\Throwable $t) {
            $this->addWarning('PostgreSQL DB server is not reachable: ' . $t->getMessage());
        }
    }
}
