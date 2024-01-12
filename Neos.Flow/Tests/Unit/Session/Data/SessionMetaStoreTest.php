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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the Flow SessionDataStore implementation
 */
class SessionMetaStoreTest extends UnitTestCase
{
    protected StringFrontend|MockObject $mockCache;

    protected SessionMetaDataStore $store;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockCache = $this->createMock(VariableFrontend::class);
        $this->store = new SessionMetaDataStore();
        $this->inject($this->store, 'cache', $this->mockCache);
        $this->inject($this->store, 'updateMetadataThreshold', 60);
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
    public function hasOperationsArePassedToTheCache(string $sessionId, bool $expectation): void
    {
        $sessionMetaData = new SessionMetaData($sessionId, '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16', time(), []);
        $this->mockCache->expects($this->once())->method('has')->with($sessionMetaData->sessionIdentifier)->willReturn($expectation);
        $this->assertEquals($expectation, $this->store->has($sessionId));
    }

    /**
     * @test
     */
    public function retrieverOperationsArePassedToTheCache(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $this->mockCache->expects($this->once())->method('get')->with($sessionMetaData->sessionIdentifier)->willReturn($sessionMetaData);
        $this->assertEquals($sessionMetaData, $this->store->retrieve($sessionMetaData->sessionIdentifier));
    }

    /**
     * @test
     */
    public function retrieverOperationsUpcastsOldArrayFormat(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $this->mockCache->expects($this->once())->method('get')->with($sessionMetaData->sessionIdentifier)->willReturn(['storageIdentifier'=>$storageId, 'tags' => [], 'lastActivityTimestamp' => $lastActivityTimestamp]);
        $this->assertEquals($sessionMetaData, $this->store->retrieve($sessionMetaData->sessionIdentifier));
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCache(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $this->mockCache->expects($this->once())->method('set')->with($sessionMetaData->sessionIdentifier, $sessionMetaData, [$sessionMetaData->sessionIdentifier], 0);

        $this->store->store($sessionMetaData);
    }

    /**
     * @test
     */
    public function storeOperationsAreNotPassedToTheCacheIfTheSameDataWasReadBefore(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 10);
        $this->mockCache->expects($this->once())->method('get')->with($sessionMetaData->sessionIdentifier)->willReturn($sessionMetaData);
        $this->mockCache->expects($this->never())->method('set');

        $this->store->retrieve($sessionMetaData->sessionIdentifier);
        $this->store->store($sessionMetaDataUpdated);
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCacheIfTheSameDataWasReadBeforeButWasOutdated(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 70);
        $this->mockCache->expects($this->once())->method('get')->with($sessionMetaData->sessionIdentifier)->willReturn($sessionMetaData);
        $this->mockCache->expects($this->once())->method('set')->with($sessionMetaDataUpdated->sessionIdentifier, $sessionMetaDataUpdated, [$sessionMetaDataUpdated->sessionIdentifier], 0);

        $this->store->retrieve($sessionMetaData->sessionIdentifier);
        $this->store->store($sessionMetaDataUpdated);
    }

    /**
     * @test
     */
    public function storeOperationsAreNotPassedToTheCacheIfTheSameDataWasStoredBefore(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 10);
        $this->mockCache->expects($this->once())->method('set')->with($sessionMetaData->sessionIdentifier, $sessionMetaData, [$sessionMetaData->sessionIdentifier], 0);

        $this->store->store($sessionMetaData);
        $this->store->store($sessionMetaDataUpdated);
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCacheIfTheSameDataWasStoredBeforeButOutdated(): void
    {
        $sessionId = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageId = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 70);
        $this->mockCache->expects($this->exactly(2))->method('set')->withConsecutive(
            [$sessionMetaData->sessionIdentifier, $sessionMetaData, [$sessionMetaData->sessionIdentifier], 0],
            [$sessionMetaData->sessionIdentifier, $sessionMetaDataUpdated, [$sessionMetaData->sessionIdentifier], 0]
        );

        $this->store->store($sessionMetaData);
        $this->store->store($sessionMetaDataUpdated);
    }
}
