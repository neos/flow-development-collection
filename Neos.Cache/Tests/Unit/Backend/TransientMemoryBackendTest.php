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
use Neos\Cache\Exception;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * Testcase for the Transient Memory Backend
 *
 */
class TransientMemoryBackendTest extends BaseTestCase
{
    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet(): void
    {
        $this->expectException(Exception::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        self::assertTrue($inCache);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($data, $fetchedData);
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache);
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache(): void
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
        self::assertEquals($otherData, $fetchedData);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        self::assertEquals($entryIdentifier, $retrieved[0]);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals($entryIdentifier, $retrieved[0]);
    }

    /**
     * @test
     * @throws Exception\NotSupportedByBackendException
     * @throws Exception
     */
    public function usingNumbersAsCacheIdentifiersWorksWhenUsingFindByTag(): void
    {
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $cache = new StringFrontend('test', $backend);
        $backend->setCache($cache);

        $data = 'Some data';
        $entryIdentifier = '12345';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1']);

        $retrieved = $cache->getByTag('UnitTestTag%tag1');
        self::assertEquals($data, current($retrieved));
    }

    /**
     * @test
     * @throws Exception
     */
    public function usingNumbersAsCacheIdentifiersWorksWhenUsingFlushTag(): void
    {
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $cache = new StringFrontend('test', $backend);
        $backend->setCache($cache);

        $data = 'Some data';
        $entryIdentifier = '12345';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1']);

        $cache->flushByTag('UnitTestTag%tag1');
        self::assertFalse($backend->has('12345'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $identifier = 'NonExistingIdentifier';
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache);
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $identifier = 'NonExistingIdentifier';
        $inCache = $backend->remove($identifier);
        self::assertFalse($inCache);
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('TransientMemoryBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('TransientMemoryBackendTest3', $data, ['UnitTestTag%test']);

        $backend->flushByTag('UnitTestTag%special');

        self::assertTrue($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        self::assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        self::assertTrue($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries(): void
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend($this->getEnvironmentConfiguration());
        $backend->setCache($cache);

        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data);
        $backend->set('TransientMemoryBackendTest2', $data);
        $backend->set('TransientMemoryBackendTest3', $data);

        $backend->flush();

        self::assertFalse($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        self::assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        self::assertFalse($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }

    /**
     * @return EnvironmentConfiguration|\PHPUnit\Framework\MockObject\MockObject
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
