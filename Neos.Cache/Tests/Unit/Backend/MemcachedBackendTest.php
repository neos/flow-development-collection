<?php
namespace Neos\Cache\Tests\Unit\Backend;

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

use Neos\Cache\Backend\MemcachedBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Frontend\AbstractFrontend;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * Testcase for the cache to memcached backend
 *
 * @requires extension memcached
 */
class MemcachedBackendTest extends BaseTestCase
{
    /**
     * Sets up this testcase
     *
     * @return void
     */
    protected function setUp(): void
    {
        try {
            if (!@fsockopen('localhost', 11211)) {
                $this->markTestSkipped('memcached not reachable');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('memcached not reachable');
        }
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $this->expectException(Exception::class);
        $backendOptions = ['servers' => ['localhost:11211']];
        $backend = new MemcachedBackend($this->getEnvironmentConfiguration(), $backendOptions);
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function initializeObjectThrowsExceptionIfNoMemcacheServerIsConfigured()
    {
        $this->expectException(Exception::class);
        $backend = new MemcachedBackend($this->getEnvironmentConfiguration(), []);
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfConfiguredServersAreUnreachable()
    {
        $this->expectException(Exception::class);
        $backend = $this->setUpBackend(['servers' => ['localhost:11212']]);
        $data = 'Somedata';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        self::assertTrue($inCache, 'Memcache failed to set and check entry');
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($data, $fetchedData, 'Memcache failed to set and retrieve data');
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache, 'Failed to set and remove data from Memcache');
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($otherData, $fetchedData, 'Memcache failed to overwrite and retrieve data');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        self::assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tagX']);
        $backend->set($identifier, $data, ['UnitTestTag%tag3']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
        self::assertEquals([], $retrieved, 'Found entry which should no longer exist.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier' . md5(uniqid(mt_rand(), true));
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache, '"has" did not return false when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier' . md5(uniqid(mt_rand(), true));
        $inCache = $backend->remove($identifier);
        self::assertFalse($inCache, '"remove" did not return false when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('BackendMemcacheTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendMemcacheTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendMemcacheTest3', $data, ['UnitTestTag%test']);

        $backend->flushByTag('UnitTestTag%special');

        self::assertTrue($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
        self::assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
        self::assertTrue($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('BackendMemcacheTest1', $data);
        $backend->set('BackendMemcacheTest2', $data);
        $backend->set('BackendMemcacheTest3', $data);

        $backend->flush();

        self::assertFalse($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
        self::assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
        self::assertFalse($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        $backendOptions = ['servers' => ['localhost:11211']];

        $thisCache = $this->getMockBuilder(AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = new MemcachedBackend($this->getEnvironmentConfiguration(), $backendOptions);
        $thisBackend->setCache($thisCache);

        $thatCache = $this->getMockBuilder(AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
        $thatBackend = new MemcachedBackend($this->getEnvironmentConfiguration(), $backendOptions);
        $thatBackend->setCache($thatCache);

        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();

        self::assertEquals('Hello', $thisBackend->get('thisEntry'));
        self::assertFalse($thatBackend->has('thatEntry'));
    }

    /**
     * Check if we can store ~5 MB of data, this gives some headroom for the
     * reflection data.
     *
     * @test
     */
    public function largeDataIsStored()
    {
        $backend = $this->setUpBackend();

        $data = str_repeat('abcde', 1024 * 1024);
        $backend->set('tooLargeData', $data);

        self::assertTrue($backend->has('tooLargeData'));
        self::assertEquals($backend->get('tooLargeData'), $data);
    }

    /**
     * Sets up the memcached backend used for testing
     *
     * @param array $backendOptions Options for the memcache backend
     * @return MemcachedBackend
     */
    protected function setUpBackend(array $backendOptions = [])
    {
        $cache = $this->createMock(FrontendInterface::class, [], [], '', false);
        if ($backendOptions == []) {
            $backendOptions = ['servers' => ['localhost:11211']];
        }
        $backend = new MemcachedBackend($this->getEnvironmentConfiguration(), $backendOptions);
        $backend->setCache($cache);

        return $backend;
    }

    /**
     * @return EnvironmentConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getEnvironmentConfiguration()
    {
        return new EnvironmentConfiguration(
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        );
    }
}
