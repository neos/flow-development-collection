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

use Neos\Cache\Backend\RedisBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * Testcase for the redis cache backend
 *
 * These tests use an actual Redis instance and will place and remove keys in db 0!
 * Since all keys have the 'TestCache:' prefix, running the tests should have
 * no side effects on non-related cache entries.
 *
 * Tests require Redis listening on 127.0.0.1:6379.
 *
 * @requires extension redis
 */
class RedisBackendTest extends BaseTestCase
{
    /**
     * @var RedisBackend
     */
    private $backend;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FrontendInterface
     */
    private $cache;

    /**
     * Set up test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        $phpredisVersion = phpversion('redis');
        if (version_compare($phpredisVersion, '1.2.0', '<')) {
            $this->markTestSkipped(sprintf('phpredis extension version %s is not supported. Please update to verson 1.2.0+.', $phpredisVersion));
        }
        try {
            if (!@fsockopen('127.0.0.1', 6379)) {
                $this->markTestSkipped('redis server not reachable');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('redis server not reachable');
        }
        $this->backend = new RedisBackend(
            new EnvironmentConfiguration('Redis a wonderful color Testing', '/some/path', PHP_MAXPATHLEN), ['hostname' => '127.0.0.1', 'database' => 0]
        );
        $this->cache = $this->createMock(FrontendInterface::class);
        $this->cache->expects(self::any())->method('getIdentifier')->will(self::returnValue('TestCache'));
        $this->backend->setCache($this->cache);
        $this->backend->flush();
    }

    /**
     * Tear down test case
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->backend instanceof RedisBackend) {
            $this->backend->flush();
        }
    }

    /**
     * @test
     */
    public function setAddsCacheEntry()
    {
        $this->backend->set('some_entry', 'foo');
        self::assertEquals('foo', $this->backend->get('some_entry'));
    }

    /**
     * @test
     */
    public function setAddsTags()
    {
        $this->backend->set('some_entry', 'foo', ['tag1', 'tag2']);
        $this->backend->set('some_other_entry', 'foo', ['tag2', 'tag3']);

        self::assertEquals(['some_entry'], $this->backend->findIdentifiersByTag('tag1'));
        $expected = ['some_entry', 'some_other_entry'];
        $actual = $this->backend->findIdentifiersByTag('tag2');

        // since Redis does not garantuee the order of values in sets, manually sort the array for comparison
        natsort($actual);
        $actual = array_values($actual);

        self::assertEquals($expected, $actual);
        self::assertEquals(['some_other_entry'], $this->backend->findIdentifiersByTag('tag3'));
    }

    /**
     * @test
     */
    public function setDoesNotAddMultipleEntries()
    {
        $this->backend->set('some_entry', 'foo');
        $this->backend->set('some_entry', 'bar');

        $entryIdentifiers = [];
        foreach ($this->backend as $entryIdentifier => $entryValue) {
            $entryIdentifiers[] = $entryIdentifier;
        }

        self::assertEquals(['some_entry'], $entryIdentifiers);
    }

    /**
     * @test
     */
    public function cacheIsIterable()
    {
        for ($i = 0; $i < 100; $i++) {
            $this->backend->set('entry_' . $i, 'foo');
        }
        $actualEntries = [];
        foreach ($this->backend as $key => $value) {
            $actualEntries[] = $key;
        }

        self::assertCount(100, $actualEntries);

        for ($i = 0; $i < 100; $i++) {
            self::assertContains('entry_' . $i, $actualEntries);
        }
    }

    /**
     * @test
     */
    public function freezeFreezesTheCache()
    {
        self::assertFalse($this->backend->isFrozen());
        for ($i = 0; $i < 10; $i++) {
            $this->backend->set('entry_' . $i, 'foo');
        }
        $this->backend->freeze();
        self::assertTrue($this->backend->isFrozen());
    }

    /**
     * @test
     */
    public function flushByTagFlushesEntryByTag()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag1', 'tag2']);
        }
        for ($i = 10; $i < 20; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag2']);
        }
        self::assertCount(10, $this->backend->findIdentifiersByTag('tag1'));
        self::assertCount(20, $this->backend->findIdentifiersByTag('tag2'));

        $count = $this->backend->flushByTag('tag1');
        self::assertEquals(10, $count, 'flushByTag returns amount of flushed entries');
        self::assertCount(0, $this->backend->findIdentifiersByTag('tag1'));
        self::assertCount(10, $this->backend->findIdentifiersByTag('tag2'));
    }

    /**
     * @test
     */
    public function flushByTagRemovesEntries()
    {
        $this->backend->set('some_entry', 'foo', ['tag1', 'tag2']);

        $this->backend->flushByTag('tag1');

        $entryIdentifiers = [];
        foreach ($this->backend as $entryIdentifier => $entryValue) {
            $entryIdentifiers[] = $entryIdentifier;
        }

        self::assertEquals([], $entryIdentifiers);
    }

    /**
     * @test
     */
    public function flushFlushesCache()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag1']);
        }
        self::assertTrue($this->backend->has('entry_5'));
        $this->backend->flush();
        self::assertFalse($this->backend->has('entry_5'));
    }

    /**
     * @test
     */
    public function removeRemovesEntryFromCache()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag1']);
        }
        self::assertCount(10, $this->backend->findIdentifiersByTag('tag1'));
        self::assertEquals('foo', $this->backend->get('entry_1'));
        $actualEntries = [];
        foreach ($this->backend as $key => $value) {
            $actualEntries[] = $key;
        }
        self::assertCount(10, $actualEntries);

        $this->backend->remove('entry_3');
        self::assertCount(9, $this->backend->findIdentifiersByTag('tag1'));
        self::assertFalse($this->backend->get('entry_3'));
        $actualEntries = [];
        foreach ($this->backend as $key => $value) {
            $actualEntries[] = $key;
        }
        self::assertCount(9, $actualEntries);
    }

    /**
     * @test
     */
    public function expiredEntriesAreSkippedWhenIterating()
    {
        $this->backend->set('entry1', 'foo', [], 1);
        sleep(2);
        self::assertFalse($this->backend->has('entry1'));

        $actualEntries = [];
        foreach ($this->backend as $key => $value) {
            $actualEntries[] = $key;
        }
        self::assertEmpty($actualEntries, 'Entries should be empty');
    }
}
