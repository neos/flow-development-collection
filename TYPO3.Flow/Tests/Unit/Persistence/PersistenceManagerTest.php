<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Persistence;

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

require_once('Fixture/Model/Entity2.php');
require_once('Fixture/Model/Entity3.php');
require_once('Fixture/Model/DirtyEntity.php');
require_once('Fixture/Model/CleanEntity.php');

/**
 * Testcase for the Persistence Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistenceManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeInitializesBackendWithBackendOptions() {
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('initialize')->with(array('Foo' => 'Bar'));

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
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
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');
		$session = new \F3\FLOW3\Persistence\Session();

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->injectReflectionService($mockReflectionService);
		$manager->injectPersistenceSession($session);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllFindsObjectReferences() {
		$entity31 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\Entity3;
		$entity32 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\Entity3;
		$entity33 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\Entity3;
		$entity2 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\Entity2;
		$entity2->someString = 'Entity2';
		$entity2->someInteger = 42;
		$entity2->someReference = $entity31;
		$entity2->someReferenceArray = array($entity32, $entity33);

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($entity2));
		$repository->add($entity2);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('F3\FLOW3\Persistence\RepositoryInterface')->will($this->returnValue(array('F3\FLOW3\Persistence\RepositoryClassName')));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with('F3\FLOW3\Persistence\RepositoryClassName')->will($this->returnValue('F3\FLOW3\Persistence\RepositoryObjectName'));
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Persistence\RepositoryObjectName')->will($this->returnValue($repository));
		$session = new \F3\FLOW3\Persistence\Session();
		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');
			// this is the really important assertion!
		$objectStorage = new \SplObjectStorage();
		$objectStorage->attach($entity2);
		$mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->injectReflectionService($mockReflectionService);
		$manager->injectObjectManager($mockObjectManager);
		$manager->injectPersistenceSession($session);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllFetchesRemovedObjects() {
		$entity1 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\CleanEntity();
		$entity3 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\CleanEntity();

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($entity1));
		$repository->remove($entity1);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('F3\FLOW3\Persistence\RepositoryInterface')->will($this->returnValue(array('F3\FLOW3\Persistence\RepositoryClassName')));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with('F3\FLOW3\Persistence\RepositoryClassName')->will($this->returnValue('F3\FLOW3\Persistence\RepositoryObjectName'));
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Persistence\RepositoryObjectName')->will($this->returnValue($repository));
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject($entity1, 'fakeUuid1');
		$session->registerReconstitutedEntity($entity1, array('identifier' => 'fakeUuid1'));
		$session->registerObject($entity3, 'fakeUuid2');
		$session->registerReconstitutedEntity($entity3, array('identifier' => 'fakeUuid2'));

		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');
			// this is the really important assertion!
		$deletedObjectStorage = new \SplObjectStorage();
		$deletedObjectStorage->attach($entity1);
		$mockBackend->expects($this->once())->method('setDeletedEntities')->with($deletedObjectStorage);

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->injectReflectionService($mockReflectionService);
		$manager->injectPersistenceSession($session);
		$manager->injectObjectManager($mockObjectManager);

		$manager->persistAll();

		$this->assertTrue($session->getReconstitutedEntities()->contains($entity3));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function persistAllResetsRepositories() {
		$entity1 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\CleanEntity();
		$entity3 = new \F3\FLOW3\Tests\Persistence\Fixture\Model\CleanEntity();

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($entity1));
		$repository->add($entity1);
		$repository->remove($entity3);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->with('F3\FLOW3\Persistence\RepositoryInterface')->will($this->returnValue(array('F3\FLOW3\Persistence\RepositoryClassName')));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectNameByClassName')->with('F3\FLOW3\Persistence\RepositoryClassName')->will($this->returnValue('F3\FLOW3\Persistence\RepositoryObjectName'));
		$mockObjectManager->expects($this->any())->method('get')->with('F3\FLOW3\Persistence\RepositoryObjectName')->will($this->returnValue($repository));

		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->injectReflectionService($mockReflectionService);
		$manager->injectObjectManager($mockObjectManager);

		$manager->persistAll();

		$this->assertEquals(0, count($repository->getAddedObjects()));
		$this->assertEquals(0, count($repository->getRemovedObjects()));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function replaceObjectUnregistersTheExistingObjectAndRegistersTheNewObjectAfterReplacingTheReconstitutedEntity() {
		$existingObject = new \stdClass();
		$newObject = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->at(0))->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('the uuid'));
		$mockSession->expects($this->at(1))->method('replaceReconstitutedEntity')->with($existingObject, $newObject);
		$mockSession->expects($this->at(2))->method('registerObject')->with($newObject, 'the uuid');

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);
		$manager->replaceObject($existingObject, $newObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsIdentifierFromSessionIfAvailable() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->once())->method('hasObject')->with($object)->will($this->returnValue(TRUE));
		$mockSession->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);

		$this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsUuidForEntitiesUnknownToSession() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();
		$object->FLOW3_Persistence_Entity_UUID = $fakeUuid;

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectPersistenceSession($this->getMock('F3\FLOW3\Persistence\Session'));

		$this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsHashForValueObjectsUnknownToSession() {
		$fakeHash = 'fakeHash';
		$object = new \stdClass();
		$object->FLOW3_Persistence_ValueObject_Hash = $fakeHash;

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectPersistenceSession($this->getMock('F3\FLOW3\Persistence\Session'));

		$this->assertEquals($manager->getIdentifierByObject($object), $fakeHash);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsNullForUnknownObjectsWithoutPersistenceMagic() {
		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectPersistenceSession($this->getMock('F3\FLOW3\Persistence\Session'));

		$this->assertNull($manager->getIdentifierByObject(new \stdClass()));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(TRUE));
		$mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
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

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(array()));

		$mockDataMapper = $this->getMock('F3\FLOW3\Persistence\DataMapper');
		$mockDataMapper->expects($this->once())->method('mapToObject')->will($this->returnValue($object));

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
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

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$mockBackend = $this->getMock('F3\FLOW3\Persistence\Backend\BackendInterface');
		$mockBackend->expects($this->once())->method('getObjectDataByIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$manager = new \F3\FLOW3\Persistence\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);
		$manager->injectBackend($mockBackend);

		$this->assertNull($manager->getObjectByIdentifier($fakeUuid));
	}

}

?>
