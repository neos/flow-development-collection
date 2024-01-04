<?php
namespace Neos\Flow\Tests\Unit\Session\Data;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Session\Data\SessionDataStore;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the Flow SessionDataStore implementation
 */
class SessionDataStoreTest extends UnitTestCase
{
    protected StringFrontend|MockObject $mockCache;

    protected SessionDataStore $store;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockCache = $this->createMock(StringFrontend::class);
        $this->store = new SessionDataStore();
        $this->store->injectCache($this->mockCache);
    }

    public function hasDataSource(): \Generator
    {
        yield "key1 exists" => ['key1', true];
        yield "key2 does not exist" => ['key2', false];
    }

    /**
     * @test
     * @dataProvider hasDataSource
     */
    public function hasOperationsArePassedToTheCache(string $key, bool $result): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('has')->with($storageId . md5($key))->willReturn($result);
        $this->assertEquals($result, $this->store->has($sessionMetaData, $key));
    }

    /**
     * @test
     */
    public function retrieverOperationsArePassedToTheCacheAndUnserializeData(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $key = 'theKey';
        $value = 'theValue';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('get')->with($storageId . md5($key))->willReturn(serialize($value));
        $this->assertEquals($value, $this->store->retrieve($sessionMetaData, $key));
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCacheAndSerializeData(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $key = 'foo';
        $value = 'bar';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('set')->with($storageId . md5($key), serialize($value), [$storageId], 0);
        $this->store->store($sessionMetaData, $key, $value);
    }

    /**
     * @test
     */
    public function removeOperationsArePassedToTheCache(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('flushByTag')->with($storageId);
        $this->store->remove($sessionMetaData);
    }

    /**
     * @test
     */
    public function afterRetrievalWritingTheSameDataIsOmitted(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $key = 'theKey';
        $value = 'theValue';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('get')->with($storageId . md5($key))->willReturn(serialize($value));
        $this->mockCache->expects($this->never())->method('set');

        $this->store->retrieve($sessionMetaData, $key);
        $this->store->store($sessionMetaData, $key, $value);
    }

    /**
     * @test
     */
    public function afterStoringWritingTheSameTwiceIsOmitted(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $key = 'theKey';
        $value = 'theValue';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('set')->with($storageId . md5($key), serialize($value), [$storageId], 0);

        $this->store->store($sessionMetaData, $key, $value);
        $this->store->store($sessionMetaData, $key, $value);
    }

    /**
     * @test
     */
    public function afterRetrievalWritingDifferentDataIsEffective(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $key = 'theKey';
        $value = 'theValue';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('get')->with($storageId . md5($key))->willReturn(serialize($value));
        $this->mockCache->expects($this->once())->method('set')->with($storageId . md5($key), serialize('otherValue'), [$storageId], 0);

        $this->store->retrieve($sessionMetaData, $key);
        $this->store->store($sessionMetaData, $key, 'otherValue');
    }

    /**
     * @test
     */
    public function afterRetrievalAndRemovalWritingTheSameDataIsEffective(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $key = 'theKey';
        $value = 'theValue';

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('get')->with($storageId . md5($key))->willReturn(serialize($value));
        $this->mockCache->expects($this->once())->method('flushByTag')->with($storageId);
        $this->mockCache->expects($this->once())->method('set')->with($storageId . md5($key), serialize($value), [$storageId], 0);

        $this->store->retrieve($sessionMetaData, $key);
        $this->store->remove($sessionMetaData);
        $this->store->store($sessionMetaData, $key, $value);
    }
}
