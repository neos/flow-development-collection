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
use Neos\Flow\Session\Data\SessionIdentifier;
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Session\Data\StorageIdentifier;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the Flow SessionDataStore implementation
 */
class SessionMetaDataStoreTest extends UnitTestCase
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
        $sessionId = SessionIdentifier::createFromString($sessionId);
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, time(), []);
        $this->mockCache->expects($this->once())->method('has')->with($sessionMetaData->sessionIdentifier->value)->willReturn($expectation);
        $this->assertEquals($expectation, $this->store->has($sessionId));
    }

    /**
     * @test
     */
    public function retrieverOperationsArePassedToTheCache(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $this->mockCache->expects($this->once())->method('get')->with($sessionId->value)->willReturn($sessionMetaData);
        $this->assertEquals($sessionMetaData, $this->store->retrieve($sessionId));
    }

    /**
     * @test
     */
    public function retrieverOperationsUpcastsOldArrayFormat(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $this->mockCache->expects($this->once())->method('get')->with($sessionId->value)->willReturn(['storageIdentifier'=>$storageId->value, 'tags' => [], 'lastActivityTimestamp' => $lastActivityTimestamp]);
        $this->assertEquals($sessionMetaData, $this->store->retrieve($sessionId));
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCache(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $this->mockCache->expects($this->once())->method('set')->with($sessionMetaData->sessionIdentifier->value, $sessionMetaData, [$sessionMetaData->sessionIdentifier->value], 0);

        $this->store->store($sessionMetaData);
    }

    /**
     * @test
     */
    public function storeOperationsAreNotPassedToTheCacheIfTheSameDataWasReadBefore(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 10);
        $this->mockCache->expects($this->once())->method('get')->with($sessionMetaData->sessionIdentifier->value)->willReturn($sessionMetaData);
        $this->mockCache->expects($this->never())->method('set');

        $this->store->retrieve($sessionMetaData->sessionIdentifier);
        $this->store->store($sessionMetaDataUpdated);
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCacheIfTheSameDataWasReadBeforeButWasOutdated(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 70);
        $this->mockCache->expects($this->once())->method('get')->with($sessionMetaData->sessionIdentifier->value)->willReturn($sessionMetaData);
        $this->mockCache->expects($this->once())->method('set')->with($sessionMetaDataUpdated->sessionIdentifier->value, $sessionMetaDataUpdated, [$sessionMetaDataUpdated->sessionIdentifier->value], 0);

        $this->store->retrieve($sessionMetaData->sessionIdentifier);
        $this->store->store($sessionMetaDataUpdated);
    }

    /**
     * @test
     */
    public function storeOperationsAreNotPassedToTheCacheIfTheSameDataWasStoredBefore(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 10);
        $this->mockCache->expects($this->once())->method('set')->with($sessionMetaData->sessionIdentifier->value, $sessionMetaData, [$sessionMetaData->sessionIdentifier->value], 0);

        $this->store->store($sessionMetaData);
        $this->store->store($sessionMetaDataUpdated);
    }

    /**
     * @test
     */
    public function storeOperationsArePassedToTheCacheIfTheSameDataWasStoredBeforeButOutdated(): void
    {
        $sessionId = SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $storageId = StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16');
        $lastActivityTimestamp = time();

        $sessionMetaData = new SessionMetaData($sessionId, $storageId, $lastActivityTimestamp, []);
        $sessionMetaDataUpdated = $sessionMetaData->withLastActivityTimestamp($lastActivityTimestamp + 70);
        $this->mockCache->expects($this->exactly(2))->method('set')->withConsecutive(
            [$sessionMetaData->sessionIdentifier->value, $sessionMetaData, [$sessionMetaData->sessionIdentifier->value], 0],
            [$sessionMetaData->sessionIdentifier->value, $sessionMetaDataUpdated, [$sessionMetaData->sessionIdentifier->value], 0]
        );

        $this->store->store($sessionMetaData);
        $this->store->store($sessionMetaDataUpdated);
    }
}
