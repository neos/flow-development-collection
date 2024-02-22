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

use Neos\Cache\Backend\BackendInterface;
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
    private static array $backends = [];

    private static ?FrontendInterface $cache = null;

    protected function tearDown(): void
    {
        foreach (self::$backends as $backend) {
            $backend->flush();
        }
    }

    public static function backendsToTest(): array
    {
        self::$cache = self::createMockCacheFrontend();
        self::initializeBackends();
        return self::$backends;
    }

    private static function createMockCacheFrontend(): FrontendInterface
    {
        return new class implements FrontendInterface
        {
            public function getIdentifier()
            {
                return 'TestCache';
            }

            public function getBackend()
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function set(string $entryIdentifier, $data, array $tags = [], int $lifetime = null)
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function get(string $entryIdentifier)
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function getByTag(string $tag): array
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function has(string $entryIdentifier): bool
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function remove(string $entryIdentifier): bool
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function flush()
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function flushByTag(string $tag): int
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function flushByTags(array $tags): int
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function collectGarbage()
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function isValidEntryIdentifier(string $identifier): bool
            {
                throw new \BadMethodCallException('Not implemented');
            }

            public function isValidTag(string $tag): bool
            {
                throw new \BadMethodCallException('Not implemented');
            }
        };
    }

    /**
     * @test
     * @dataProvider backendsToTest
     */
    public function setAddsCacheEntry(BackendInterface $backend): void
    {
        $backend->flush();

        // use data that contains binary junk
        $data = random_bytes(2048);
        $backend->set('some_entry', $data);
        self::assertEquals($data, $backend->get('some_entry'));
    }

    /**
     * @test
     * @dataProvider backendsToTest
     */
    public function cacheEntriesCanBeIterated(BackendInterface $backend): void
    {
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

        self::assertEquals(3, $entries);
    }

    private static function initializeBackends(): void
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
            $backend->setCache(self::$cache);
            $backend->flush();
            self::$backends['sqlite'] = [$backend];
        } catch (\Throwable $t) {
            //$this->addWarning('SQLite DB is not reachable: ' . $t->getMessage());
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
            $backend->setCache(self::$cache);
            $backend->flush();

            self::$backends['mysql'] = [$backend];
        } catch (\Throwable $t) {
            //$this->addWarning('MySQL DB server is not reachable: ' . $t->getMessage());
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
            $backend->setCache(self::$cache);
            $backend->flush();
            self::$backends['pgsql'] = [$backend];
        } catch (\Throwable $t) {
            //$this->addWarning('PostgreSQL DB server is not reachable: ' . $t->getMessage());
        }
    }
}
