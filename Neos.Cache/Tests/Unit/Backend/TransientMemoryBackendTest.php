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

use Neos\Cache\Backend\TransientMemoryBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * Testcase for the Transient Memory Backend
 *
 */
class TransientMemoryBackendTest extends BaseTestCase
{
    /**
     * @expectedException \Neos\Cache\Exception
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        $this->assertTrue($inCache);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($data, $fetchedData);
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        $this->assertFalse($inCache);
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($otherData, $fetchedData);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        $this->assertEquals($entryIdentifier, $retrieved[0]);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($entryIdentifier, $retrieved[0]);
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $identifier = 'NonExistingIdentifier';
        $inCache = $backend->has($identifier);
        $this->assertFalse($inCache);
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $identifier = 'NonExistingIdentifier';
        $inCache = $backend->remove($identifier);
        $this->assertFalse($inCache);
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('TransientMemoryBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('TransientMemoryBackendTest3', $data, ['UnitTestTag%test']);

        $backend->flushByTag('UnitTestTag%special');

        $this->assertTrue($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        $this->assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        $this->assertTrue($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data);
        $backend->set('TransientMemoryBackendTest2', $data);
        $backend->set('TransientMemoryBackendTest3', $data);

        $backend->flush();

        $this->assertFalse($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        $this->assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        $this->assertFalse($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }

    /**
     * @return EnvironmentConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getEnvironmentConfiguration()
    {
        return $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();
    }
}
