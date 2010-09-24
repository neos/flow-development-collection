<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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

/**
 * Testcase for the base Repository
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RepositoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$repository = new \F3\FLOW3\Persistence\Repository;
		$this->assertTrue($repository instanceof \F3\FLOW3\Persistence\RepositoryInterface);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new \stdClass();
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($someObject));
		$repository->add($someObject);

		$this->assertTrue($repository->getAddedObjects()->contains($someObject));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$object3 = new \stdClass();

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($object1));
		$repository->add($object1);
		$repository->add($object2);
		$repository->add($object3);

		$repository->remove($object2);

		$this->assertTrue($repository->getAddedObjects()->contains($object1));
		$this->assertFalse($repository->getAddedObjects()->contains($object2));
		$this->assertTrue($repository->getAddedObjects()->contains($object3));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$object1 = new \ArrayObject(array('val' => '1'));
		$object2 = new \ArrayObject(array('val' => '2'));
		$object3 = new \ArrayObject(array('val' => '3'));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($object1));
		$repository->add($object1);
		$repository->add($object2);
		$repository->add($object3);

		$object2['foo'] = 'bar';
		$object3['val'] = '2';

		$repository->remove($object2);

		$this->assertTrue($repository->getAddedObjects()->contains($object1));
		$this->assertFalse($repository->getAddedObjects()->contains($object2));
		$this->assertTrue($repository->getAddedObjects()->contains($object3));
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
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($object));
		$repository->remove($object);

		$this->assertTrue($repository->getRemovedObjects()->contains($object));
	}

	/**
	 * dataProvider for createQueryCallsQueryFactoryWithExpectedType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function modelAndRepositoryClassNames() {
		return array(
			array('\F3\Blog\Domain\Repository\BlogRepository', '\F3\Blog\Domain\Model\Blog'),
			array('﻿\Domain\Repository\Content\PageRepository', '﻿\Domain\Model\Content\Page')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function constructSetsObjectTypeFromClassName($repositoryClassName, $modelClassName) {
		$mockClassName = 'MockRepository' . uniqid();
		eval('class ' . $mockClassName . ' extends \F3\FLOW3\Persistence\Repository {
			protected function FLOW3_AOP_Proxy_getProxyTargetClassName() {
				return \'' . $repositoryClassName . '\';
			}
			public function _getObjectType() {
				return $this->objectType;
			}
		}');

		$repository = new $mockClassName();
		$this->assertEquals($modelClassName, $repository->_getObjectType());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQueryCallsQueryFactoryWithExpectedClassName() {
		$mockQueryFactory = $this->getMock('F3\FLOW3\Persistence\QueryFactoryInterface');
		$mockQueryFactory->expects($this->once())->method('create')->with('ExpectedType');

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedType');
		$repository->injectQueryFactory($mockQueryFactory);

		$repository->createQuery();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQuerySetsDefaultOrderingIfDefined() {
		$orderings = array('foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING);
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
		$mockQueryFactory = $this->getMock('F3\FLOW3\Persistence\QueryFactoryInterface');
		$mockQueryFactory->expects($this->exactly(2))->method('create')->will($this->returnValue($mockQuery));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectQueryFactory($mockQueryFactory);
		$repository->setDefaultOrderings($orderings);
		$repository->createQuery();

		$repository->setDefaultOrderings(array());
		$repository->createQuery();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');

		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($expectedResult, $repository->findAll());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findByUuidReturnsResultOfGetObjectByIdentifierCall() {
		$fakeUUID = '123-456';
		$object = new \stdClass();

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($fakeUUID)->will($this->returnValue($object));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', 'stdClass');

		$this->assertSame($object, $repository->findByUuid($fakeUUID));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findByUuidReturnsNullIfObjectOfMismatchingTypeWasFoundByGetObjectByIdentifierCall() {
		$fakeUUID = '123-456';
		$object = new \stdClass();

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($fakeUUID)->will($this->returnValue($object));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', 'otherExpectedClass');

		$this->assertNULL($repository->findByUuid($fakeUUID));
	}

	/**
	 * Replacing a reconstituted object (which has a uuid) by a new object
	 * will ask the persistence backend to replace them accordingly in the
	 * identity map.
	 *
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function replaceReconstitutedEntityByNewObject() {
		$existingObject = new \stdClass;
		$newObject = new \stdClass;

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceManager->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($newObject));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->replace($existingObject, $newObject);
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
	 */
	public function replaceReconstituedObjectWhichIsMarkedToBeRemoved() {
		$existingObject = new \stdClass;
		$newObject = new \stdClass;

		$removedObjects = new \SplObjectStorage;
		$removedObjects->attach($existingObject);

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceManager->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($newObject));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('removedObjects', $removedObjects);
		$repository->replace($existingObject, $newObject);

		$this->assertFalse($removedObjects->contains($existingObject));
		$this->assertTrue($removedObjects->contains($newObject));
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

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue(NULL));
		$mockPersistenceManager->expects($this->never())->method('replaceObject');

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', get_class($newObject));
		$repository->_set('addedObjects', $addedObjects);
		$repository->replace($existingObject, $newObject);

		$this->assertFalse($addedObjects->contains($existingObject));
		$this->assertTrue($addedObjects->contains($newObject));
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function replaceChecksObjectType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->replace(new \stdClass(), new \stdClass());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateReplacesAnObjectWithTheSameUuidByTheGivenObject() {
		$existingObject = new \stdClass;
		$modifiedObject = $this->getMock('FooBar' . uniqid(), array('FLOW3_Persistence_isClone'));
		$modifiedObject->expects($this->once())->method('FLOW3_Persistence_isClone')->will($this->returnValue(TRUE));

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($modifiedObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue($existingObject));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('replaceObject'));
		$repository->expects($this->once())->method('replaceObject')->with($existingObject, $modifiedObject);

		$repository->_set('objectType', get_class($modifiedObject));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->update($modifiedObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateRejectsNonClonedObjects() {
		$someObject = $this->getMock('FooBar' . uniqid(), array('FLOW3_Persistence_isClone'));
		$someObject->expects($this->once())->method('FLOW3_Persistence_isClone')->will($this->returnValue(FALSE));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', get_class($someObject));

		$repository->update($someObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateRejectsObjectsOfWrongType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'Foo');
		$repository->update(new \stdClass());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function updateRecursivelyCallsUpdateObjectOnSubobjects() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('updateObject'));
		$className = 'Object' . uniqid();
		eval('class ' . $className . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface {
			public function FLOW3_Persistence_isNew() { return FALSE; }
			public function FLOW3_Persistence_isClone() { return TRUE; }
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function FLOW3_AOP_Proxy_getProperty($name) {}
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

		$repository->expects($this->once())->method('updateObject')->with($subObject);
		$repository->_call('updateRecursively', $object);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new \stdClass();
		$mockQueryResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($object, $repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame(2, $repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addChecksObjectType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->add(new \stdClass());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeChecksObjectType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->remove(new \stdClass());
	}
}

?>
