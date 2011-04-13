<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Persistence\Generic;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/../Fixture/Model/Entity2.php');
require_once(__DIR__ . '/../Fixture/Model/Entity3.php');
require_once(__DIR__ . '/../Fixture/Model/DirtyEntity.php');
require_once(__DIR__ . '/../Fixture/Model/CleanEntity.php');

/**
 * Testcase for the Persistence Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistenceManagerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeInitializesBackendWithBackendOptions() {
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Generic\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('initialize')->with(array('Foo' => 'Bar'));

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectBackend($mockBackend);

		$manager->injectSettings(array('persistence' => array('backendOptions' => array('Foo' => 'Bar'))));
		$manager->initialize();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function persistAllCanBeCalledIfNoRepositoryClassesAreFound() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue(array()));
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Generic\Backend\BackendInterface');
		$session = new \F3\FLOW3\Persistence\Generic\Session();

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->injectReflectionService($mockReflectionService);
		$manager->injectPersistenceSession($session);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllPassesAddedObjectsToBackend() {
		$entity2 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\Entity2();
		$objectStorage = new \SplObjectStorage();
		$objectStorage->attach($entity2);
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Generic\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->add($entity2);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllPassesRemovedObjectsToBackend() {
		$entity2 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\Entity2();
		$objectStorage = new \SplObjectStorage();
		$objectStorage->attach($entity2);
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Generic\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->remove($entity2);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsIdentifierFromSession() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);

		$this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(TRUE));
		$mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);

		$this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Generic\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(array()));

		$mockDataMapper = $this->getMock('F3\FLOW3\Persistence\Generic\DataMapper');
		$mockDataMapper->expects($this->once())->method('mapToObject')->will($this->returnValue($object));

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);
		$manager->injectBackend($mockBackend);
		$manager->injectDataMapper($mockDataMapper);

		$this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByIdentifierReturnsNullForUnknownObject() {
		$fakeUuid = 'fakeUuid';

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Generic\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$manager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);
		$manager->injectBackend($mockBackend);

		$this->assertNull($manager->getObjectByIdentifier($fakeUuid));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new \stdClass();
		$persistenceManager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$persistenceManager->add($someObject);

		$this->assertAttributeContains($someObject, 'addedObjects', $persistenceManager);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$object3 = new \stdClass();

		$persistenceManager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$persistenceManager->add($object1);
		$persistenceManager->add($object2);
		$persistenceManager->add($object3);

		$persistenceManager->remove($object2);

		$this->assertAttributeContains($object1, 'addedObjects', $persistenceManager);
		$this->assertAttributeNotContains($object2, 'addedObjects', $persistenceManager);
		$this->assertAttributeContains($object3, 'addedObjects', $persistenceManager);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$object1 = new \ArrayObject(array('val' => '1'));
		$object2 = new \ArrayObject(array('val' => '2'));
		$object3 = new \ArrayObject(array('val' => '3'));

		$persistenceManager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$persistenceManager->add($object1);
		$persistenceManager->add($object2);
		$persistenceManager->add($object3);

		$object2['foo'] = 'bar';
		$object3['val'] = '2';

		$persistenceManager->remove($object2);

		$this->assertAttributeContains($object1, 'addedObjects', $persistenceManager);
		$this->assertAttributeNotContains($object2, 'addedObjects', $persistenceManager);
		$this->assertAttributeContains($object3, 'addedObjects', $persistenceManager);
	}

	/**
	 * Make sure we remember the objects that are not currently add()ed
	 * but might be in persistent storage.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeRetainsObjectForObjectsNotInCurrentSession() {
		$object = new \ArrayObject(array('val' => '1'));
		$persistenceManager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$persistenceManager->remove($object);

		$this->assertAttributeContains($object, 'removedObjects', $persistenceManager);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mergeReplacesAnObjectWithTheSameUuidByTheGivenObject() {
		$existingObject = new \stdClass();
		$modifiedObject = clone $existingObject;

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session', array('getIdentifierByObject', 'getObjectByIdentifier', 'hasIdentifier', 'replaceReconstitutedEntity', 'registerObject'));
		$mockPersistenceSession->expects($this->once())->method('getIdentifierByObject')->with($modifiedObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceSession->expects($this->once())->method('hasIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->once())->method('getObjectByIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue($existingObject));
		$mockPersistenceSession->expects($this->once())->method('replaceReconstitutedEntity')->with($existingObject, $modifiedObject);
		$mockPersistenceSession->expects($this->once())->method('registerObject')->with($modifiedObject, '86ea8820-19f6-11de-8c30-0800200c9a66');
		$persistenceManager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$persistenceManager->injectPersistenceSession($mockPersistenceSession);

		$persistenceManager->merge($modifiedObject);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mergeRecursesOnSubobjects() {
		$className = 'Object' . uniqid();
		eval('class ' . $className . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface {
			public function FLOW3_Persistence_isClone() { return TRUE; }
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() {}
			public function __clone() {}
		}');
		$subObject = $this->getMock($className);
		$subObject->expects($this->once())->method('FLOW3_Persistence_isClone')->will($this->returnValue(TRUE));

		$className = 'Object' . uniqid();
		eval('class ' . $className . '  {
			protected $subobject;
			public function getSubobject() {
				return $this->subobject;
			}
			public function setSubobject($subobject) {
				$this->subobject = $subobject;
			}
		}');
		$object = new $className;
		$object->setSubobject($subObject);

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session', array('getIdentifierByObject', 'getObjectByIdentifier', 'hasIdentifier', 'replaceReconstitutedEntity', 'registerObject'));
		$mockPersistenceSession->expects($this->at(0))->method('getIdentifierByObject')->with($object)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceSession->expects($this->at(1))->method('hasIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->at(2))->method('getObjectByIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue($object));
		$mockPersistenceSession->expects($this->at(5))->method('getIdentifierByObject')->with($subObject)->will($this->returnValue('86ea8820-19f6-subo-8c30-0800200c9a66'));
		$mockPersistenceSession->expects($this->at(6))->method('hasIdentifier')->with('86ea8820-19f6-subo-8c30-0800200c9a66')->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->at(7))->method('getObjectByIdentifier')->with('86ea8820-19f6-subo-8c30-0800200c9a66')->will($this->returnValue($subObject));

		$persistenceManager = new \F3\FLOW3\Persistence\Generic\PersistenceManager();
		$persistenceManager->injectPersistenceSession($mockPersistenceSession);
		$persistenceManager->merge($object);
	}

	/**
	 * Replacing a reconstituted object which during this session has been
	 * marked for removal (by calling the repository's remove method)
	 * additionally registers the "newObject" for removal and removes the
	 * "existingObject" from the list of removed objects.
	 *
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function replaceReconstituedObjectWhichIsMarkedToBeRemoved() {
		$existingObject = new \stdClass;
		$modifiedObject = new \stdClass;

		$removedObjects = new \SplObjectStorage;
		$removedObjects->attach($existingObject);

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session', array('getIdentifierByObject', 'getObjectByIdentifier', 'hasIdentifier', 'replaceReconstitutedEntity', 'registerObject'));
		$mockPersistenceSession->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceSession->expects($this->once())->method('hasIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->once())->method('getObjectByIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue($existingObject));

		$mockPersistenceManager = $this->getAccessibleMock('F3\FLOW3\Persistence\Generic\PersistenceManager', array('dummy'));
		$mockPersistenceManager->_set('objectType', get_class($modifiedObject));
		$mockPersistenceManager->_set('removedObjects', $removedObjects);
		$mockPersistenceManager->injectPersistenceSession($mockPersistenceSession);
		$mockPersistenceManager->merge($modifiedObject);

		$this->assertFalse($removedObjects->contains($existingObject), 'Existing object has not been removed from removed objects set.');
		$this->assertTrue($removedObjects->contains($modifiedObject), 'Modified object has not been added to removed objects set.');
	}

	/**
	 * Replacing a new object which has not yet been persisted by another
	 * new object will just replace them in the repository's list of added
	 * objects.
	 *
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function replaceNewObjectByNewObject() {
		$existingObject = new \stdClass;
		$newObject = new \stdClass;

		$addedObjects = new \SplObjectStorage;
		$addedObjects->attach($existingObject);

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Generic\Session', array('getIdentifierByObject', 'getObjectByIdentifier', 'hasIdentifier', 'replaceReconstitutedEntity', 'registerObject'));
		$mockPersistenceSession->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceSession->expects($this->once())->method('hasIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->once())->method('getObjectByIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue($existingObject));

		$mockPersistenceManager = $this->getAccessibleMock('F3\FLOW3\Persistence\Generic\PersistenceManager', array('dummy'));
		$mockPersistenceManager->_set('objectType', get_class($newObject));
		$mockPersistenceManager->_set('addedObjects', $addedObjects);
		$mockPersistenceManager->injectPersistenceSession($mockPersistenceSession);
		$mockPersistenceManager->merge($newObject);

		$this->assertFalse($addedObjects->contains($existingObject), 'Existing object has not been removed from added objects set.');
		$this->assertTrue($addedObjects->contains($newObject), 'Modified object has not been added to added objects set.');
	}

}

?>
