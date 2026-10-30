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
        if (version_compare($phpredisVersion, '5.0.0', '<')) {
            $this->markTestSkipped(sprintf('phpredis extension version %s is not supported. Please update to version 5.0.0+.', $phpredisVersion));
        }
        try {
            if (!@fsockopen('127.0.0.1', 6379)) {
                $this->markTestSkipped('redis server not reachable');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('redis server not reachable');
        }
        $this->backend = new RedisBackend(
            new EnvironmentConfiguration('Redis a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
            ['hostname' => '127.0.0.1', 'database' => 0]
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
    public function flushUnfreezesTheCache()
    {
        self::assertFalse($this->backend->isFrozen());
        $this->backend->freeze();
        self::assertTrue($this->backend->isFrozen());
        $this->backend->flush();
        self::assertFalse($this->backend->isFrozen());
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
    public function flushByTagsFlushesEntryByTags()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag1', 'tag2']);
        }
        for ($i = 10; $i < 20; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag2']);
        }
        for ($i = 20; $i < 30; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag3']);
        }
        self::assertCount(10, $this->backend->findIdentifiersByTag('tag1'));
        self::assertCount(20, $this->backend->findIdentifiersByTag('tag2'));
        self::assertCount(10, $this->backend->findIdentifiersByTag('tag3'));

        $count = $this->backend->flushByTags(['tag1', 'tag3']);
        self::assertEquals(20, $count, 'flushByTag returns amount of flushed entries');
        self::assertCount(0, $this->backend->findIdentifiersByTag('tag1'));
        self::assertCount(10, $this->backend->findIdentifiersByTag('tag2'));
        self::assertCount(0, $this->backend->findIdentifiersByTag('tag3'));
    }

    /**
     * @test
     */
    public function flushByTagsReturnsTotalOfAllBatches()
    {
        $this->backend->setBatchSize(2);
        $tags = [];
        for ($i = 0; $i < 5; $i++) {
            $this->backend->set('entry_' . $i, 'foo', ['tag' . $i]);
            $tags[] = 'tag' . $i;
        }

        $count = $this->backend->flushByTags($tags);

        self::assertSame(5, $count, 'flushByTags returns the amount of flushed entries over all batches');
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
    public function flushByTagsRemovesEntries()
    {
        $this->backend->set('some_entry', 'foo', ['tag1', 'tag2']);
        $this->backend->set('some_other_entry', 'bar', ['tag3']);

        $this->backend->flushByTags(['tag1', 'tag3']);

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

    /**
     * @test
     */
    public function newIterationIncludesEntriesSetAfterPreviousIteration(): void
    {
        $this->backend->set('first', 'firstData');
        $this->backend->set('second', 'secondData');
        \iterator_to_array($this->backend);

        $this->backend->set('third', 'thirdData');

        $data = \iterator_to_array($this->backend);
        ksort($data);
        self::assertSame(['first' => 'firstData', 'second' => 'secondData', 'third' => 'thirdData'], $data);
    }

    /**
     * @test
     */
    public function newIterationExcludesEntriesRemovedAfterPreviousIteration(): void
    {
        $this->backend->set('first', 'firstData');
        \iterator_to_array($this->backend);

        $this->backend->remove('first');

        self::assertEmpty(\iterator_to_array($this->backend));
    }

    /**
     * @test
     */
    public function newIterationIsEmptyAfterFlush(): void
    {
        $this->backend->set('first', 'firstData');
        \iterator_to_array($this->backend);

        $this->backend->flush();

        self::assertEmpty(\iterator_to_array($this->backend));
    }

    /**
     * @test
     */
    public function newIterationExcludesEntriesFlushedByTag(): void
    {
        $this->backend->set('first', 'firstData', ['tag']);
        \iterator_to_array($this->backend);

        $this->backend->flushByTag('tag');

        self::assertEmpty(\iterator_to_array($this->backend));
    }

    /**
     * @test
     */
    public function iterationIsNotRestartedWhenAnEntryIsUpdatedWhileIterating(): void
    {
        foreach (['first', 'second', 'third', 'fourth'] as $entryIdentifier) {
            $this->backend->set($entryIdentifier, $entryIdentifier . 'Data');
        }

        $visitedEntryIdentifiers = [];
        foreach ($this->backend as $entryIdentifier => $data) {
            // update while in the middle of the iteration – a restart would visit earlier entries again
            if (count($visitedEntryIdentifiers) === 2) {
                $this->backend->set($entryIdentifier, 'updatedData');
            }
            $visitedEntryIdentifiers[] = $entryIdentifier;
        }

        sort($visitedEntryIdentifiers);
        self::assertSame(['first', 'fourth', 'second', 'third'], $visitedEntryIdentifiers);
    }

    /**
     * @test
     */
    public function iterationIsNotRestartedWhenAnEntryIsRemovedWhileIterating(): void
    {
        foreach (['first', 'second', 'third', 'fourth'] as $entryIdentifier) {
            $this->backend->set($entryIdentifier, $entryIdentifier . 'Data');
        }

        $visitedEntryIdentifiers = [];
        foreach ($this->backend as $entryIdentifier => $data) {
            // remove an entry that has been visited already
            if (count($visitedEntryIdentifiers) === 2) {
                $this->backend->remove($visitedEntryIdentifiers[0]);
            }
            $visitedEntryIdentifiers[] = $entryIdentifier;
        }

        sort($visitedEntryIdentifiers);
        self::assertSame(['first', 'fourth', 'second', 'third'], $visitedEntryIdentifiers);
    }

    /**
     * @test
     */
    public function iterationVisitsAllEntriesWhenTheCurrentEntryIsRemovedWhileIterating(): void
    {
        $entryIdentifiers = [];
        for ($i = 1; $i <= 10; $i++) {
            $entryIdentifiers[] = 'entry' . $i;
            $this->backend->set('entry' . $i, 'data' . $i);
        }

        $visitedEntryIdentifiers = [];
        foreach ($this->backend as $entryIdentifier => $data) {
            // remove every entry right when it is visited, like SessionManager::collectGarbage() does
            $this->backend->remove($entryIdentifier);
            $visitedEntryIdentifiers[] = $entryIdentifier;
        }

        sort($visitedEntryIdentifiers);
        sort($entryIdentifiers);
        self::assertSame($entryIdentifiers, $visitedEntryIdentifiers);
    }

    /**
     * @test
     */
    public function iterationSkipsEntriesRemovedBeforeTheyAreVisited(): void
    {
        $entryIdentifiers = [];
        for ($i = 1; $i <= 10; $i++) {
            $entryIdentifiers[] = 'entry' . $i;
            $this->backend->set('entry' . $i, 'data' . $i);
        }

        $visitedEntryIdentifiers = [];
        $removedEntryIdentifier = null;
        foreach ($this->backend as $entryIdentifier => $data) {
            // remove an entry that has not been visited yet – this must neither end the iteration nor yield it
            if ($removedEntryIdentifier === null) {
                $removedEntryIdentifier = current(array_diff($entryIdentifiers, [$entryIdentifier]));
                $this->backend->remove($removedEntryIdentifier);
            }
            self::assertNotFalse($data);
            $visitedEntryIdentifiers[] = $entryIdentifier;
        }

        $expectedEntryIdentifiers = array_values(array_diff($entryIdentifiers, [$removedEntryIdentifier]));
        sort($visitedEntryIdentifiers);
        sort($expectedEntryIdentifiers);
        self::assertSame($expectedEntryIdentifiers, $visitedEntryIdentifiers);
    }
}
