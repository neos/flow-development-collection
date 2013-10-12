<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Generic\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for \TYPO3\Flow\Persistence\Backend
 *
 */
class AbstractBackendTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function commitDelegatesToPersistObjectsAndProcessDeletedObjects() {
		$backend = $this->getMock('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend', array('persistObjects', 'processDeletedObjects', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject'));
		$backend->expects($this->once())->method('persistObjects');
		$backend->expects($this->once())->method('processDeletedObjects');
		$backend->commit();
	}

	/**
	 * @test
	 */
	public function persistObjectsPassesObjectsToPersistObject() {
		$objects = new \SplObjectStorage();
		$objects->attach(new \stdClass());
		$objects->attach(new \stdClass());

		$mockPersistenceSession = $this->getMock('TYPO3\Flow\Persistence\Generic\Session');
		$backend = $this->getAccessibleMock('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend', array('persistObject', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject'));

		$backend->injectPersistenceSession($mockPersistenceSession);
		$backend->expects($this->exactly(2))->method('persistObject');
		$backend->setAggregateRootObjects($objects);
		$backend->_call('persistObjects');
	}

	/**
	 * @test
	 */
	public function processDeletedObjectsPassesObjectsToRemoveEntity() {
		$object = new \stdClass();
		$objects = new \SplObjectStorage();
		$objects->attach($object);

		$mockSession = $this->getMock('TYPO3\Flow\Persistence\Generic\Session');
		$mockSession->expects($this->at(0))->method('hasObject')->with($object)->will($this->returnValue(TRUE));
		$mockSession->expects($this->at(1))->method('unregisterReconstitutedEntity')->with($object);
		$mockSession->expects($this->at(2))->method('unregisterObject')->with($object);

		$backend = $this->getAccessibleMock('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend', array('getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject'));
		$backend->injectPersistenceSession($mockSession);
		$backend->expects($this->once())->method('removeEntity')->with($object);
		$backend->setDeletedEntities($objects);
		$backend->_call('processDeletedObjects');
	}

	/**
	 * @test
	 */
	public function processDeletedObjectsPassesOnlyKnownObjectsToRemoveEntity() {
		$object = new \stdClass();
		$objects = new \SplObjectStorage();
		$objects->attach($object);

		$mockSession = $this->getMock('TYPO3\Flow\Persistence\Generic\Session');
		$mockSession->expects($this->at(0))->method('hasObject')->with($object)->will($this->returnValue(FALSE));
		$mockSession->expects($this->never())->method('unregisterObject');

		$backend = $this->getAccessibleMock('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend', array('getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier', 'removeEntity', 'removeValueObject', 'storeObject'));
		$backend->injectPersistenceSession($mockSession);
		$backend->expects($this->never())->method('removeEntity');
		$backend->setDeletedEntities($objects);
		$backend->_call('processDeletedObjects');
	}

	/**
	 * @test
	 */
	public function getTypeNormalizesDoubleToFloat() {
		$backend = $this->getAccessibleMockForAbstractClass('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend');
		$this->assertEquals('float', $backend->_call('getType', 1.234));
	}

	/**
	 * @test
	 */
	public function getTypeReturnsClassNameForObjects() {
		$backend = $this->getAccessibleMockForAbstractClass('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend');
		$this->assertEquals('stdClass', $backend->_call('getType', new \stdClass()));
	}

	/**
	 * @test
	 */
	public function arrayContainsObjectReturnsTrueForSameObject() {
		$object = new \stdClass();

		$mockSession = $this->getMock('TYPO3\Flow\Persistence\Generic\Session');

		$backend = $this->getAccessibleMockForAbstractClass('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend');
		$backend->injectPersistenceSession($mockSession);

		$this->assertTrue($backend->_call('arrayContainsObject', array($object), $object, 'fakeUuid'));
	}

	/**
	 * @test
	 */
	public function arrayContainsObjectReturnsFalseForDifferentObject() {
		$mockSession = $this->getMock('TYPO3\Flow\Persistence\Generic\Session');
		$mockSession->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('uuid2'));

		$backend = $this->getAccessibleMockForAbstractClass('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend');
		$backend->injectPersistenceSession($mockSession);

		$this->assertFalse($backend->_call('arrayContainsObject', array(new \stdClass()), new \stdClass(), 'uuid1'));
	}

	/**
	 * @test
	 */
	public function arrayContainsObjectReturnsFalseForClone() {
		$object = new \stdClass();
		$clone = clone $object;

		$mockSession = $this->getMock('TYPO3\Flow\Persistence\Generic\Session');
		$mockSession->expects($this->any())->method('getIdentifierByObject')->with($object)->will($this->returnValue('fakeUuid'));

		$backend = $this->getAccessibleMockForAbstractClass('TYPO3\Flow\Persistence\Generic\Backend\AbstractBackend');
		$backend->injectPersistenceSession($mockSession);

		$this->assertFalse($backend->_call('arrayContainsObject', array($object), $clone, 'clonedFakeUuid'));
	}

}
