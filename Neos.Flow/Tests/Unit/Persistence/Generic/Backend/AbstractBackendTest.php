<?php
namespace Neos\Flow\Tests\Unit\Persistence\Generic\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for \Neos\Flow\Persistence\Backend
 */
class AbstractBackendTest extends UnitTestCase
{
    /**
     * @test
     */
    public function commitDelegatesToPersistObjectsAndProcessDeletedObjects()
    {
        $backend = $this->getMockBuilder(Persistence\Generic\Backend\AbstractBackend::class)->setMethods(['persistObjects', 'processDeletedObjects', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject', 'isConnected'])->getMock();
        $backend->expects(self::once())->method('persistObjects');
        $backend->expects(self::once())->method('processDeletedObjects');
        $backend->commit();
    }

    /**
     * @test
     */
    public function persistObjectsPassesObjectsToPersistObject()
    {
        $objects = new \SplObjectStorage();
        $objects->attach(new \stdClass());
        $objects->attach(new \stdClass());

        $mockPersistenceSession = $this->createMock(Persistence\Generic\Session::class);
        $backend = $this->getAccessibleMock(Persistence\Generic\Backend\AbstractBackend::class, ['persistObject', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject', 'isConnected']);

        $backend->injectPersistenceSession($mockPersistenceSession);
        $backend->expects(self::exactly(2))->method('persistObject');
        $backend->setAggregateRootObjects($objects);
        $backend->_call('persistObjects');
    }

    /**
     * @test
     */
    public function processDeletedObjectsPassesObjectsToRemoveEntity()
    {
        $object = new \stdClass();
        $objects = new \SplObjectStorage();
        $objects->attach($object);

        $mockSession = $this->createMock(Persistence\Generic\Session::class);
        $mockSession->expects(self::at(0))->method('hasObject')->with($object)->will(self::returnValue(true));
        $mockSession->expects(self::at(1))->method('unregisterReconstitutedEntity')->with($object);
        $mockSession->expects(self::at(2))->method('unregisterObject')->with($object);

        $backend = $this->getAccessibleMock(Persistence\Generic\Backend\AbstractBackend::class, ['getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject', 'isConnected']);
        $backend->injectPersistenceSession($mockSession);
        $backend->expects(self::once())->method('removeEntity')->with($object);
        $backend->setDeletedEntities($objects);
        $backend->_call('processDeletedObjects');
    }

    /**
     * @test
     */
    public function processDeletedObjectsPassesOnlyKnownObjectsToRemoveEntity()
    {
        $object = new \stdClass();
        $objects = new \SplObjectStorage();
        $objects->attach($object);

        $mockSession = $this->createMock(Persistence\Generic\Session::class);
        $mockSession->expects(self::at(0))->method('hasObject')->with($object)->will(self::returnValue(false));
        $mockSession->expects(self::never())->method('unregisterObject');

        $backend = $this->getAccessibleMock(Persistence\Generic\Backend\AbstractBackend::class, ['getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject', 'isConnected']);
        $backend->injectPersistenceSession($mockSession);
        $backend->expects(self::never())->method('removeEntity');
        $backend->setDeletedEntities($objects);
        $backend->_call('processDeletedObjects');
    }

    /**
     * @test
     */
    public function getTypeNormalizesDoubleToFloat()
    {
        $backend = $this->getAccessibleMockForAbstractClass(Persistence\Generic\Backend\AbstractBackend::class);
        self::assertEquals('float', $backend->_call('getType', 1.234));
    }

    /**
     * @test
     */
    public function getTypeReturnsClassNameForObjects()
    {
        $backend = $this->getAccessibleMockForAbstractClass(Persistence\Generic\Backend\AbstractBackend::class);
        self::assertEquals('stdClass', $backend->_call('getType', new \stdClass()));
    }

    /**
     * @test
     */
    public function arrayContainsObjectReturnsTrueForSameObject()
    {
        $object = new \stdClass();

        $mockSession = $this->createMock(Persistence\Generic\Session::class);

        $backend = $this->getAccessibleMockForAbstractClass(Persistence\Generic\Backend\AbstractBackend::class);
        $backend->injectPersistenceSession($mockSession);

        self::assertTrue($backend->_call('arrayContainsObject', [$object], $object, 'fakeUuid'));
    }

    /**
     * @test
     */
    public function arrayContainsObjectReturnsFalseForDifferentObject()
    {
        $mockSession = $this->createMock(Persistence\Generic\Session::class);
        $mockSession->expects(self::any())->method('getIdentifierByObject')->will(self::returnValue('uuid2'));

        $backend = $this->getAccessibleMockForAbstractClass(Persistence\Generic\Backend\AbstractBackend::class);
        $backend->injectPersistenceSession($mockSession);

        self::assertFalse($backend->_call('arrayContainsObject', [new \stdClass()], new \stdClass(), 'uuid1'));
    }

    /**
     * @test
     */
    public function arrayContainsObjectReturnsFalseForClone()
    {
        $object = new \stdClass();
        $clone = clone $object;

        $mockSession = $this->createMock(Persistence\Generic\Session::class);
        $mockSession->expects(self::any())->method('getIdentifierByObject')->with($object)->will(self::returnValue('fakeUuid'));

        $backend = $this->getAccessibleMockForAbstractClass(Persistence\Generic\Backend\AbstractBackend::class);
        $backend->injectPersistenceSession($mockSession);

        self::assertFalse($backend->_call('arrayContainsObject', [$object], $clone, 'clonedFakeUuid'));
    }
}
