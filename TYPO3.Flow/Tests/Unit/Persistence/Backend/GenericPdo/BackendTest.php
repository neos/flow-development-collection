<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend\GenericPdo;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
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

require_once(__DIR__ . '/../../Fixture/AnEntity.php');
require_once(__DIR__ . '/../../Fixture/AValue.php');

/**
 * Testcase for \F3\FLOW3\Persistence\Backend\GenericPdo\Backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->loadPdoInterface();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeCallsToParentAndConnectsToDatabase() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchemata');
		$backend = $this->getMock('F3\FLOW3\Persistence\Backend\GenericPdo\Backend', array('connect'));
		$backend->expects($this->once())->method('connect');
		$backend->injectReflectionService($mockReflectionService);
		$backend->initialize(array());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasEntityRecordEmitsExpectedSql() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeUuid'));
		$mockStatement->expects($this->once())->method('fetchColumn');
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT COUNT("identifier") FROM "entities" WHERE "identifier"=?')->will($this->returnValue($mockStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('hasEntityRecord', 'fakeUuid');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasValueobjectRecordEmitsExpectedSql() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeHash'));
		$mockStatement->expects($this->once())->method('fetchColumn');
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT COUNT("identifier") FROM "valueobjects" WHERE "identifier"=?')->will($this->returnValue($mockStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('hasValueobjectRecord', 'fakeHash');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removePropertiesEmitsExpectedSql() {
		$mockDeletePropertyStatement = $this->getMock('PDOStatement');
		$mockDeletePropertyStatement->expects($this->once())->method('execute')->with(array('identifier', 'propertyname'));
		$mockDeleteDataStatement = $this->getMock('PDOStatement');
		$mockDeleteDataStatement->expects($this->once())->method('execute')->with(array('identifier', 'propertyname'));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->at(0))->method('prepare')->with('DELETE FROM "properties" WHERE "parent"=? AND "name"=?')->will($this->returnValue($mockDeletePropertyStatement));
		$mockPdo->expects($this->at(1))->method('prepare')->with('DELETE FROM "properties_data" WHERE "parent"=? AND "name"=?')->will($this->returnValue($mockDeleteDataStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('removeProperties', array('propertyname' => array('parent' => 'identifier')));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removePropertiesByParentEmitsExpectedSql() {
		$parent = new \stdClass();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($parent, 'identifier');
		$mockDeletePropertyStatement = $this->getMock('PDOStatement');
		$mockDeletePropertyStatement->expects($this->once())->method('execute')->with(array('identifier'));
		$mockDeleteDataStatement = $this->getMock('PDOStatement');
		$mockDeleteDataStatement->expects($this->once())->method('execute')->with(array('identifier'));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->at(0))->method('prepare')->with('DELETE FROM "properties_data" WHERE "parent"=?')->will($this->returnValue($mockDeleteDataStatement));
		$mockPdo->expects($this->at(1))->method('prepare')->with('DELETE FROM "properties" WHERE "parent"=?')->will($this->returnValue($mockDeletePropertyStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('removePropertiesByParent', $parent);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectCreatesRecordOnlyForNewObject() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\FLOW3\Persistence\Tests\\' . $className;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\';}
		}');
		$newObject = new $fullClassName();
		$oldObject = new $fullClassName();

		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($oldObject, '');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'emitPersistedObject'));
		$backend->expects($this->exactly(2))->method('emitPersistedObject');
		$backend->expects($this->once())->method('createObjectRecord');
		$backend->injectPersistenceSession($persistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->_set('classSchemata', array($fullClassName => new \F3\FLOW3\Reflection\ClassSchema($fullClassName)));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('persistObject', $newObject);
		$backend->_call('persistObject', $oldObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see http://forge.typo3.org/issues/show/3859
	 */
	public function persistObjectsHandlesCyclicReferences() {
		$namespace = 'F3\FLOW3\Persistence\Tests';
		$className1 = 'SomeClass' . uniqid();
		$fullClassName1 = $namespace . '\\' . $className1;
		eval('namespace ' . $namespace . '; class ' . $className1 . ' implements \F3\FLOW3\AOP\ProxyInterface {
			protected $FLOW3_Persistence_Entity_UUID = \'A\';
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); }
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { return TRUE; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$className2 = 'SomeClass' . uniqid();
		$fullClassName2 = $namespace . '\\' . $className2;
		eval('namespace ' . $namespace . '; class ' . $className2 . ' implements \F3\FLOW3\AOP\ProxyInterface {
			protected $FLOW3_Persistence_Entity_UUID = \'B\';
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); }
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { return TRUE; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$className3 = 'SomeClass' . uniqid();
		$fullClassName3 = $namespace . '\\' . $className3;
		eval('namespace ' . $namespace . '; class ' . $className3 . ' implements \F3\FLOW3\AOP\ProxyInterface {
			protected $FLOW3_Persistence_Entity_UUID = \'C\';
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); }
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { return TRUE; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$objectA = new $fullClassName1();
		$objectB = new $fullClassName2();
		$objectC = new $fullClassName3();
		$objectA->sub = $objectB;
		$objectB->sub = $objectC;
		$objectC->sub = $objectB;
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($objectA);

		$classSchema1 = new \F3\FLOW3\Reflection\ClassSchema($fullClassName1);
		$classSchema1->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema1->addProperty('sub', $fullClassName2);
		$classSchema1->setAggregateRoot(TRUE);
		$classSchema2 = new \F3\FLOW3\Reflection\ClassSchema($fullClassName2);
		$classSchema2->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema2->addProperty('sub', $fullClassName3);
		$classSchema3 = new \F3\FLOW3\Reflection\ClassSchema($fullClassName3);
		$classSchema3->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema3->addProperty('sub', $fullClassName2);

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session', array('hasObject'));
		$mockSession->expects($this->at(0))->method('hasObject')->with($this->attribute($this->equalTo('A'), 'FLOW3_Persistence_Entity_UUID'))->will($this->returnValue(FALSE));
			// the following fails although the same object is present, nethr equalTo nor identicalTo work...
		//$mockSession->expects($this->at(0))->method('hasObject')->/*with($this->identicalTo($objectA))->*/will($this->returnValue(FALSE));
		$mockSession->expects($this->at(1))->method('hasObject')->with($this->attribute($this->equalTo('B'), 'FLOW3_Persistence_Entity_UUID'))->will($this->returnValue(FALSE));
		$mockSession->expects($this->at(2))->method('hasObject')->with($this->attribute($this->equalTo('C'), 'FLOW3_Persistence_Entity_UUID'))->will($this->returnValue(FALSE));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'setProperties', 'emitPersistedObject'));
		$backend->expects($this->exactly(3))->method('createObjectRecord')->will($this->onConsecutiveCalls('A', 'B', 'C'));
		$excpectedPropertiesOfA = array(
			'identifier' => 'A',
			'classname' => $fullClassName1,
			'properties' => array(
				'sub' => array(
					'type' => $fullClassName2,
					'multivalue' => FALSE,
					'value' => array(
						'identifier' => 'B'
					)
				)
			)
		);
		$excpectedPropertiesOfB = array(
			'identifier' => 'B',
			'classname' => $fullClassName2,
			'properties' => array(
				'sub' => array(
					'type' => $fullClassName3,
					'multivalue' => FALSE,
					'value' => array(
						'identifier' => 'C'
					)
				)
			)
		);
		$excpectedPropertiesOfC = array(
			'identifier' => 'C',
			'classname' => $fullClassName3,
			'properties' => array(
				'sub' => array(
					'type' => $fullClassName2,
					'multivalue' => FALSE,
					'value' => array(
						'identifier' => 'B'
					)
				)
			)
		);
		$backend->expects($this->at(3))->method('setProperties')->with($excpectedPropertiesOfC);
		$backend->expects($this->at(5))->method('setProperties')->with($excpectedPropertiesOfB);
		$backend->expects($this->at(7))->method('setProperties')->with($excpectedPropertiesOfA);
		$backend->injectPersistenceSession($mockSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->_set('classSchemata', array($fullClassName1 => $classSchema1, $fullClassName2 => $classSchema2, $fullClassName3 => $classSchema3));

		$backend->_call('persistObjects');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function uuidPropertyNameFromNewObjectIsUsedForRecord() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($name) { if ($name === \'idProp\') return \'' . $identifier . '\'; }
		}');
		$newObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->addProperty('idProp', 'string');
		$classSchema->setUUIDPropertyName('idProp');

		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array($identifier, $fullClassName));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('INSERT INTO "entities" ("identifier", "type") VALUES (?, ?)')->will($this->returnValue($mockStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('createObjectRecord', $newObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function uuidOfNewEntityIsUsedForRecord() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' implements \F3\FLOW3\AOP\ProxyInterface {
			protected $FLOW3_Persistence_Entity_UUID = \'' . $identifier . '\';
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { return TRUE; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$newObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);

		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array($identifier, $fullClassName));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('INSERT INTO "entities" ("identifier", "type") VALUES (?, ?)')->will($this->returnValue($mockStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('createObjectRecord', $newObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hashOfNewValueObjectIsUsedForRecord() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$hash = sha1($fullClassName);
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' implements \F3\FLOW3\AOP\ProxyInterface {
			protected $FLOW3_Persistence_ValueObject_Hash = \'' . $hash . '\';
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { if ($propertyName === \'FLOW3_Persistence_ValueObject_Hash\') { return TRUE; } else { return FALSE; } }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$newObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);

		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array($hash, $fullClassName));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('INSERT INTO "valueobjects" ("identifier", "type") VALUES (?, ?)')->will($this->returnValue($mockStatement));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('hasValueObjectRecord'));
		$backend->expects($this->once())->method('hasValueObjectRecord')->with($hash)->will($this->returnValue(FALSE));
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('createObjectRecord', $newObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectProcessesDirtyObject() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface{
			public $simpleString = \'simpleValue\';
			public function FLOW3_Persistence_isClone() {}
			public function __clone() {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
		}');
		$dirtyObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->addProperty('simpleString', 'string');
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session', array('isDirty'));
		$mockPersistenceSession->expects($this->once())->method('isDirty')->will($this->returnValue(TRUE));
		$mockPersistenceSession->registerObject($dirtyObject, $identifier);

		$expectedProperties = array(
			'identifier' => $identifier,
			'classname' => $fullClassName,
			'properties' => array(
				'simpleString' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'simpleValue'
				)
			)
		);
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('setProperties', 'emitPersistedObject'));
		$backend->expects($this->once())->method('setProperties')->with($expectedProperties);
		$backend->expects($this->once())->method('emitPersistedObject', \F3\FLOW3\Persistence\Backend\AbstractBackend::OBJECTSTATE_RECONSTITUTED);
		$backend->injectPersistenceSession($mockPersistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_set('classSchemata', array($fullClassName => $classSchema));

		$backend->_call('persistObject', $dirtyObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectProcessesObjectsWithDateTimeMember() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface {
			public $date;
			public function FLOW3_Persistence_isClone() {}
			public function __clone() {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\';}
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
		}');
		$newObject = new $fullClassName();
		$date = new \DateTime();
		$newObject->date = $date;
		$newObject->FLOW3_Persistence_Entity_UUID = NULL;

		$expectedProperties = array(
			'identifier' => NULL,
			'classname' => $fullClassName,
			'properties' => array(
				'date' => array(
					'type' => 'DateTime',
					'multivalue' => FALSE,
					'value' => $date->getTimestamp()
				)
			)
		);

		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($newObject, '');
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->addProperty('date', 'DateTime');

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('setProperties', 'emitPersistedObject'));
		$backend->expects($this->once())->method('setProperties')->with($expectedProperties);
		$backend->injectPersistenceSession($persistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_call('persistObject', $newObject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectCallsCheckPropertyType() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public $simpleString = \'simpleValue\';
			protected $dirty = TRUE;
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
		}');
		$object = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->addProperty('simpleString', 'string');
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($object, $identifier);

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('checkType', 'setProperties', 'emitPersistedObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_set('classSchemata', array($fullClassName => $classSchema));

			// ... and here we go
		$backend->expects($this->once())->method('checkType')->with('string', 'simpleValue');
		$backend->_call('persistObject', $object);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valueObjectsAreStoredOnceAndReusedAsNeeded() {
			// set up objects
		$A = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('A');
		$A->FLOW3_Persistence_Entity_UUID = 'fakeUuidA';
		$B = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('B');
		$B->FLOW3_Persistence_Entity_UUID = 'fakeUuidB';
		$V = new \F3\TYPO3CR\Tests\Fixtures\AValue('V');
		$V->FLOW3_Persistence_ValueObject_Hash = 'fakeHash';
		$A->add($V);
		$B->add($V);
		$B->add($V);
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($A);
		$aggregateRootObjects->attach($B);

		$expectedPropertiesForA = array(
			'identifier' => 'fakeUuidA',
			'classname' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
			'properties' => array(
				'name' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'A',
				),
				'members' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'F3\TYPO3CR\Tests\Fixtures\AValue',
							'index' => '0',
							'value' => array(
								'identifier' => 'fakeHash'
							)
						)
					)
				)
			)
		);
		$expectedPropertiesForB = array(
			'identifier' => 'fakeUuidB',
			'classname' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
			'properties' => array(
				'name' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'B',
				),
				'members' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'F3\TYPO3CR\Tests\Fixtures\AValue',
							'index' => '0',
							'value' => array(
								'identifier' => 'fakeHash'
							)
						),
						array(
							'type' => 'F3\TYPO3CR\Tests\Fixtures\AValue',
							'index' => '1',
							'value' => array(
								'identifier' => 'fakeHash'
							)
						)
					)
				)
			)
		);

			// set up needed infrastructure
		$entityClassSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\TYPO3CR\Tests\Fixtures\AnEntity');
		$entityClassSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$entityClassSchema->addProperty('name', 'string');
		$entityClassSchema->addProperty('members', 'array');
		$valueClassSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\TYPO3CR\Tests\Fixtures\AValue');
		$valueClassSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);
		$valueClassSchema->addProperty('name', 'string');

			// ... and here we go
		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session', array('hasObject'));
		$mockSession->expects($this->exactly(3))->method('hasObject')->will($this->onConsecutiveCalls(FALSE, FALSE, FALSE));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'setProperties', 'emitPersistedObject'));
		$backend->expects($this->at(0))->method('createObjectRecord')->with($A)->will($this->returnValue('fakeUuidA'));
		$backend->expects($this->at(1))->method('createObjectRecord')->with($V)->will($this->returnValue('fakeHash'));
		$backend->expects($this->at(4))->method('setProperties')->with($expectedPropertiesForA);
		$backend->expects($this->at(6))->method('createObjectRecord')->with($B)->will($this->returnValue('fakeUuidB'));
		$backend->expects($this->at(7))->method('setProperties')->with($expectedPropertiesForB);

		$backend->injectPersistenceSession($mockSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->_set('classSchemata', array('F3\TYPO3CR\Tests\Fixtures\AnEntity' => $entityClassSchema, 'F3\TYPO3CR\Tests\Fixtures\AValue' => $valueClassSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('persistObject', $A);
		$backend->_call('persistObject', $B);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function splObjectStorageIsStoredAsExpected() {
			// set up object
		$A = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('A');
		$A->FLOW3_Persistence_Entity_UUID = 'fakeUuuidA';
		$B = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('B');
		$B->FLOW3_Persistence_Entity_UUID = 'fakeUuuidB';
		$A->addObject($B);

		$expectedPropertiesForB = array(
			'identifier' => 'fakeUuidB',
			'classname' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
			'properties' => array(
				'name' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'B'
				),
				'objects' => array(
					'type' => 'SplObjectStorage',
					'multivalue' => TRUE,
					'value' => array()
				)
			)
		);
		$expectedPropertiesForA = array(
			'identifier' => 'fakeUuidA',
			'classname' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
			'properties' => array(
				'name' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'A'
				),
				'objects' => array(
					'type' => 'SplObjectStorage',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
							'index' => NULL,
							'value' => array('identifier' => 'fakeUuidB'),
						),
					)
				)
			)
		);

			// set up needed infrastructure
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\TYPO3CR\Tests\Fixtures\AnEntity');
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->addProperty('name', 'string');
		$classSchema->addProperty('objects', 'SplObjectStorage');
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($A, 'fakeUuidA');
		$persistenceSession->registerObject($B, 'fakeUuidB');

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'setProperties', 'emitPersistedObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->expects($this->never())->method('createObjectRecord');
		$backend->expects($this->at(0))->method('setProperties')->with($expectedPropertiesForB);
		$backend->expects($this->at(2))->method('setProperties')->with($expectedPropertiesForA);

		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_set('classSchemata', array('F3\TYPO3CR\Tests\Fixtures\AnEntity' => $classSchema));
		$backend->_call('persistObject', $A);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function dateTimeInSplObjectStorageIsStoredAsExpected() {
			// set up object
		$A = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('A');
		$A->FLOW3_Persistence_Entity_UUID = 'fakeUuidA';
		$dateTime = new \DateTime;
		$A->addObject($dateTime);

		$expectedPropertiesForA = array(
			'identifier' => 'fakeUuidA',
			'classname' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
			'properties' => array(
				'name' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'A'
				),
				'objects' => array(
					'type' => 'SplObjectStorage',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'DateTime',
							'index' => NULL,
							'value' => $dateTime->getTimestamp()
						),
					)
				)
			)
		);

			// set up needed infrastructure
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\TYPO3CR\Tests\Fixtures\AnEntity');
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->addProperty('name', 'string');
		$classSchema->addProperty('objects', 'SplObjectStorage');
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($A, 'fakeUuidA');

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'setProperties', 'emitPersistedObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->expects($this->never())->method('createObjectRecord');
		$backend->expects($this->once())->method('setProperties')->with($expectedPropertiesForA);

		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_set('classSchemata', array('F3\TYPO3CR\Tests\Fixtures\AnEntity' => $classSchema));
		$backend->_call('persistObject', $A);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function entitiesDetachedFromSplObjectStorageAreRemovedFromRepository() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
		}');
		$object = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);

		$objectStorage = new \SplObjectStorage();
		$objectStorage->attach($object);
		$previousObjectStorage = clone $objectStorage;
		$objectStorage->detach($object);

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeEntity'));
		$backend->expects($this->once())->method('removeEntity')->with($object);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_call('processSplObjectStorage', $objectStorage, $identifier, $previousObjectStorage);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valueObjectsDetachedFromSplObjectStorageAreRemovedFromRepository() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
		}');
		$object = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);

		$objectStorage = new \SplObjectStorage();
		$objectStorage->attach($object);
		$previousObjectStorage = clone $objectStorage;
		$objectStorage->detach($object);

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeValueObject'));
		$backend->expects($this->once())->method('removeValueObject')->with($object);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_call('processSplObjectStorage', $objectStorage, $identifier, $previousObjectStorage);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function entitiesRemovedFromArrayAreRemovedFromRepository() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
		}');
		$object = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);

		$array = array();
		$previousArray = array($object);

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeEntity'));
		$backend->expects($this->once())->method('removeEntity')->with($object);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_call('processArray', $array, $identifier, $previousArray);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valueObjectsRemovedFromArrayAreRemovedFromRepository() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
		}');
		$object = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);

		$array = array();
		$previousArray = array($object);

			// ... and here we go
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeValueObject'));
		$backend->expects($this->once())->method('removeValueObject')->with($object);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_call('processArray', $array, $identifier, $previousArray);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function dateTimeAndLiteralsInArrayAreProcessedAsExpected() {
		$dateTime = new \DateTime();
		$array = array('foo' => 'bar', 'date' => $dateTime);

		$expected = array(
			array(
				'type' => 'string',
				'index' => 'foo',
				'value' => 'bar'
			),
			array(
				'type' => 'DateTime',
				'index' => 'date',
				'value' => $dateTime->getTimestamp()
			)
		);

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));

		$result = $backend->_call('processArray', $array, 'fakeUuid');
		$this->assertEquals($result, $expected);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processArrayRejectsNestedArrays() {
		$array = array(array('foo' => 'bar'));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));

		$backend->_call('processArray', $array, 'fakeUuid');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processArrayRejectsNestedSplObjectStorageInsideArray() {
		$array = array(new \SplObjectStorage());

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));

		$backend->_call('processArray', $array, 'fakeUuid');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processDateTimeHandlesNullInputByReturningNull() {
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));

		$this->assertNull($backend->_call('processDateTime', NULL, 'fakeUuid'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processArrayHandlesNullInputByReturningNull() {
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));

		$this->assertNull($backend->_call('processArray', NULL, 'fakeUuid'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processSplObjectStorageHandlesNullInputByReturningNull() {
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));

		$this->assertNull($backend->_call('processSplObjectStorage', NULL, 'fakeUuid'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function aggregateRootObjectsFoundWhenPersistingThatAreNotAmongAggregateRootObjectsCollectedFromRepositoriesArePersisted() {
		$otherClassName = 'OtherClass' . uniqid();
		$fullOtherClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $otherClassName;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $otherClassName . ' implements \F3\FLOW3\AOP\ProxyInterface {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullOtherClassName . '\';}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { return TRUE; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return NULL; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$someClassName = 'SomeClass' . uniqid();
		$fullSomeClassName = 'F3\\FLOW3\Persistence\\Tests\\' . $someClassName;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $someClassName . ' implements \F3\FLOW3\AOP\ProxyInterface {
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullSomeClassName . '\';}
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) { return TRUE; }
			public function FLOW3_AOP_Proxy_getProperty($propertyName) { return $this->$propertyName; }
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $value) {}
		}');
		$otherAggregateRootObject = new $fullOtherClassName();
		$someAggregateRootObject = new $fullSomeClassName();
		$someAggregateRootObject->property = $otherAggregateRootObject;

		$otherClassSchema = new \F3\FLOW3\Reflection\ClassSchema($otherClassName);
		$otherClassSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$otherClassSchema->setAggregateRoot(TRUE);
		$someClassSchema = new \F3\FLOW3\Reflection\ClassSchema($someClassName);
		$someClassSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$someClassSchema->setAggregateRoot(TRUE);
		$someClassSchema->addProperty('property', $fullOtherClassName);

		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($someAggregateRootObject);

		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($someAggregateRootObject, '');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'setProperties', 'emitPersistedObject'));
		$backend->expects($this->once())->method('createObjectRecord')->with($otherAggregateRootObject);
		$backend->injectPersistenceSession($persistenceSession);
		$backend->injectValidatorResolver($this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE));
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_set('classSchemata', array(
			$fullOtherClassName => $otherClassSchema,
			$fullSomeClassName => $someClassSchema
		));
		$backend->_call('persistObjects');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPropertiesByParentEmitsExpectedSql() {
		$propertyData = array(
			'identifier' => 'identifier',
			'classname' => 'F3\TYPO3CR\Tests\Fixtures\AnEntity',
			'properties' => array(
				'singleValue' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'propertyValue',
				),
				'multiValue' => array(
					'type' => 'SplObjectStorage',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'DateTime',
							'index' => NULL,
							'value' => 1
						),
						array(
							'type' => 'DateTime',
							'index' => NULL,
							'value' => 2
						)
					)
				),
				'keyedMultiValue' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => '\FooBar',
							'index' => 'one',
							'value' => '1234'
						),
						array(
							'type' => '\FooBar',
							'index' => 'two',
							'value' => '5678'
						)
					)
				)
			)
		);


		$mockInsertPropertyStatement = $this->getMock('PDOStatement');
		$mockInsertPropertyStatement->expects($this->at(0))->method('execute')->with(array('identifier', 'singleValue', 0, 'string'));
		$mockInsertDataStatement = $this->getMock('PDOStatement');
		$mockInsertDataStatement->expects($this->at(0))->method('execute')->with(array('identifier', 'singleValue', NULL, 'string', 'propertyValue'));
		$mockInsertDataStatement->expects($this->at(1))->method('execute')->with(array('identifier', 'multiValue', NULL, 'DateTime', '1'));
		$mockInsertDataStatement->expects($this->at(2))->method('execute')->with(array('identifier', 'multiValue', NULL, 'DateTime', '2'));
		$mockInsertDataStatement->expects($this->at(3))->method('execute')->with(array('identifier', 'keyedMultiValue', 'one', '\FooBar', '1234'));
		$mockInsertDataStatement->expects($this->at(4))->method('execute')->with(array('identifier', 'keyedMultiValue', 'two', '\FooBar', '5678'));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->at(0))->method('prepare')->with('INSERT INTO "properties" ("parent", "name", "multivalue", "type") VALUES (?, ?, ?, ?)')->will($this->returnValue($mockInsertPropertyStatement));
		$mockPdo->expects($this->at(1))->method('prepare')->with('INSERT INTO "properties_data" ("parent", "name", "index", "type", "string") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockInsertDataStatement));
		$mockPdo->expects($this->at(2))->method('prepare')->with('INSERT INTO "properties_data" ("parent", "name", "index", "type", "datetime") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockInsertDataStatement));
		$mockPdo->expects($this->at(3))->method('prepare')->with('INSERT INTO "properties_data" ("parent", "name", "index", "type", "datetime") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockInsertDataStatement));
		$mockPdo->expects($this->at(4))->method('prepare')->with('INSERT INTO "properties_data" ("parent", "name", "index", "type", "object") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockInsertDataStatement));
		$mockPdo->expects($this->at(5))->method('prepare')->with('INSERT INTO "properties_data" ("parent", "name", "index", "type", "object") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockInsertDataStatement));

		$backendProxyClassName = $this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend');
		$backend = new $backendProxyClassName();
		$backend->injectPersistenceSession(new \F3\FLOW3\Persistence\Session());
		$backend->_set('databaseHandle', $mockPdo);

		$backend->_call('setProperties', $propertyData, \F3\FLOW3\Persistence\Backend\AbstractBackend::OBJECTSTATE_NEW);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeEntitiesByParentEmitsExpectedSql() {
		$fooBarClassSchema = new \F3\FLOW3\Reflection\ClassSchema('FooBar');
		$fooBarClassSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
		$fooBarClassSchema->setAggregateRoot(TRUE);
		$quuxClassSchema = new \F3\FLOW3\Reflection\ClassSchema('Quux');
		$quuxClassSchema->setModelType(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);

		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeUuid1'));
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->onConsecutiveCalls(array(array('type' => 'FooBar', 'identifier' => 'heretostay'), array('type' => 'Quux', 'identifier' => 'goaway'))));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT "identifier", "type" FROM "entities" WHERE "identifier" IN (SELECT DISTINCT "object" FROM "properties_data" WHERE "parent"=?)')->will($this->returnValue($mockStatement));

		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($object1, 'fakeUuid1');
		$persistenceSession->registerObject($object2, 'goaway');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeEntity', 'emitRemovedObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->expects($this->once())->method('removeEntity')->with($object2);
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_set('classSchemata', array(
			'FooBar' => $fooBarClassSchema,
			'Quux' => $quuxClassSchema
		));

		$backend->_call('removeEntitiesByParent', $object1);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeValueObjectsByParentEmitsExpectedSql() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeUuid'));
		$mockStatement->expects($this->exactly(3))->method('fetchColumn')->will($this->onConsecutiveCalls('fakeHash1', 'fakeHash2', FALSE));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT "identifier" FROM "valueobjects" WHERE "identifier" IN (SELECT DISTINCT "object" FROM "properties_data" WHERE "parent"=?)')->will($this->returnValue($mockStatement));

		$parent = new \stdClass();
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($parent, 'fakeUuid');
		$persistenceSession->registerObject($object1, 'fakeHash1');
		$persistenceSession->registerObject($object2, 'fakeHash2');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('getValueObjectUsageCount', 'removeValueObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->expects($this->at(0))->method('getValueObjectUsageCount')->with($object1)->will($this->returnValue(2));
		$backend->expects($this->at(1))->method('getValueObjectUsageCount')->with($object2)->will($this->returnValue(1));
		$backend->expects($this->at(2))->method('removeValueObject')->with($object2);
		$backend->_set('databaseHandle', $mockPdo);

		$backend->_call('removeValueObjectsByParent', $parent);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeEntityEmitsExpectedSqlAndRemovedObjectSignal() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeUuid'));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('DELETE FROM "entities" WHERE "identifier"=?')->will($this->returnValue($mockStatement));

		$object = new \stdClass();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($object, 'fakeUuid');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeEntitiesByParent', 'removeValueObjectsByParent', 'removePropertiesByParent', 'emitRemovedObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->expects($this->once())->method('emitRemovedObject')->with($object);
		$backend->expects($this->once())->method('removeEntitiesByParent')->with($object);
		$backend->expects($this->once())->method('removeValueObjectsByParent')->with($object);
		$backend->expects($this->once())->method('removePropertiesByParent')->with($object);
		$backend->_set('databaseHandle', $mockPdo);

		$backend->_call('removeEntity', $object);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeValueObjectEmitsExpectedSqlAndRemovedObjectSignal($subject = 'fakeHash') {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeHash'));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('DELETE FROM "valueobjects" WHERE "identifier"=?')->will($this->returnValue($mockStatement));

		$subject = new \stdClass();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($subject, 'fakeHash');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('removeValueObjectsByParent', 'removePropertiesByParent', 'emitRemovedObject'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->expects($this->once())->method('emitRemovedObject')->with($subject);
		$backend->expects($this->once())->method('removeValueObjectsByParent')->with($subject);
		$backend->expects($this->once())->method('removePropertiesByParent')->with($subject);
		$backend->_set('databaseHandle', $mockPdo);

		$backend->_call('removeValueObject', $subject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValueObjectUsageCountEmitsExpectedSql($subject = 'fakeHash') {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeHash'));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT COUNT(DISTINCT "parent") FROM "properties_data" WHERE "object"=?')->will($this->returnValue($mockStatement));

		$subject = new \stdClass();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$persistenceSession->registerObject($subject, 'fakeHash');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('dummy'));
		$backend->injectPersistenceSession($persistenceSession);
		$backend->_set('databaseHandle', $mockPdo);

		$backend->_call('getValueObjectUsageCount', $subject);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectCountByQueryCountsQueryResult() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('getObjectDataByQuery'));
		$backend->expects($this->once())->method('getObjectDataByQuery')->with($mockQuery)->will($this->returnValue(array(1,2,3)));
		$this->assertEquals(3, $backend->_call('getObjectCountByQuery', $mockQuery));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataInitializesKnownRecordsArray() {
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('_getObjectData'));
		$backend->_set('knownRecords', FALSE);
		$backend->_call('getObjectDataByIdentifier', '');
		$this->assertEquals(array(), $backend->_get('knownRecords'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function _getObjectDataFetchesIdentifierAndTypeForEntities() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('e2408ea7-9742-48d6-9aab-df85d78120ae'));
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('QUERY_RESULT')));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT "identifier", "type" AS "classname" FROM "entities" WHERE "identifier"=?')->will($this->returnValue($mockStatement));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('processObjectRecords'));
		$backend->expects($this->once())->method('processObjectRecords')->will($this->returnValue(array()));
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('_getObjectData', 'e2408ea7-9742-48d6-9aab-df85d78120ae');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function _getObjectDataFetchesIdentifierAndTypeForValueObjects() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute')->with(array('fakeHash'));
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('QUERY_RESULT')));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT "identifier", "type" AS "classname" FROM "valueobjects" WHERE "identifier"=?')->will($this->returnValue($mockStatement));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('processObjectRecords'));
		$backend->expects($this->once())->method('processObjectRecords')->will($this->returnValue(array()));
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('_getObjectData', 'fakeHash');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function _getObjectDataCallsProcessObjectRecordsAndReturnsResult() {
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('QUERY_RESULT')));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SELECT "identifier", "type" AS "classname" FROM "valueobjects" WHERE "identifier"=?')->will($this->returnValue($mockStatement));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('processObjectRecords'));
		$backend->expects($this->once())->method('processObjectRecords')->with(array('QUERY_RESULT'))->will($this->returnValue(array('RESULT')));
		$backend->_set('databaseHandle', $mockPdo);
		$this->assertEquals('RESULT', $backend->_call('_getObjectData', 'fakeHash'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByQueryInitializesKnownRecordsArray() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('QUERY_RESULT')));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->will($this->returnValue($mockStatement));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('buildQuery', 'processObjectRecords'));
		$backend->expects($this->once())->method('processObjectRecords')->will($this->returnValue(array()));
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_set('knownRecords', FALSE);
		$backend->_call('getObjectDataByQuery', $mockQuery);
		$this->assertEquals(array(), $backend->_get('knownRecords'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByQueryDelegatesQueryBuildingAndUsesResultForDatabaseQuery() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute');
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('QUERY_RESULT')));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->with('SQLSTRING')->will($this->returnValue($mockStatement));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('buildQuery', 'processObjectRecords'));
		$backend->expects($this->once())->method('processObjectRecords')->will($this->returnValue(array()));
		$backend->expects($this->once())->method('buildQuery')->with($mockQuery, array())->will($this->returnValue('SQLSTRING'));
		$backend->_set('databaseHandle', $mockPdo);
		$backend->_call('getObjectDataByQuery', $mockQuery);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByQueryCallsProcessObjectRecordsWithQueryResult() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockStatement = $this->getMock('PDOStatement');
		$mockStatement->expects($this->once())->method('execute');
		$mockStatement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('QUERY_RESULT')));
		$mockPdo = $this->getMock('PdoInterface');
		$mockPdo->expects($this->once())->method('prepare')->will($this->returnValue($mockStatement));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('buildQuery', 'processObjectRecords'));
		$backend->expects($this->once())->method('processObjectRecords')->with(array('QUERY_RESULT'))->will($this->returnValue(array('OBJECTS')));
		$backend->_set('databaseHandle', $mockPdo);
		$this->assertEquals(array('OBJECTS'), $backend->_call('getObjectDataByQuery', $mockQuery));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectAsksForValidatorsForNewAndDirtyObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\FLOW3\Persistence\Tests\\' . $className;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\';}
		}');
		$newObject = new $fullClassName();
		$oldObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->addProperty('foo', 'string');

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->exactly(2))->method('hasObject')->will($this->onConsecutiveCalls(FALSE, TRUE));
		$mockPersistenceSession->expects($this->exactly(2))->method('isDirty')->will($this->onConsecutiveCalls(TRUE, TRUE));

		$mockValidatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->exactly(2))->method('getBaseValidatorConjunction')->with($fullClassName);

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'emitPersistedObject', 'setProperties'));
		$backend->injectPersistenceSession($mockPersistenceSession);
		$backend->injectValidatorResolver($mockValidatorResolver);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('persistObject', $newObject);
		$backend->_call('persistObject', $oldObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Persistence\Exception\ObjectValidationFailedException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectThrowsExceptionOnValidationFailureForNewObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\FLOW3\Persistence\Tests\\' . $className;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\';}
		}');
		$newObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->addProperty('foo', 'string');

		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$mockValidator->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->will($this->returnValue(FALSE));

		$mockValidatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->with($fullClassName)->will($this->returnValue($mockValidator));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'emitPersistedObject', 'setProperties'));
		$backend->injectPersistenceSession($mockPersistenceSession);
		$backend->injectValidatorResolver($mockValidatorResolver);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('persistObject', $newObject);
	}
	/**
	 * @test
	 * @expectedException \F3\FLOW3\Persistence\Exception\ObjectValidationFailedException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectThrowsExceptionOnValidationFailureForOldObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\FLOW3\Persistence\Tests\\' . $className;
		eval('namespace F3\\FLOW3\Persistence\\Tests; class ' . $className . ' {
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return \'' . $fullClassName . '\';}
		}');
		$oldObject = new $fullClassName();

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema($fullClassName);
		$classSchema->addProperty('foo', 'string');

		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$mockValidator->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->once())->method('isDirty')->will($this->returnValue(TRUE));

		$mockValidatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->with($fullClassName)->will($this->returnValue($mockValidator));

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\GenericPdo\Backend'), array('createObjectRecord', 'emitPersistedObject', 'setProperties'));
		$backend->injectPersistenceSession($mockPersistenceSession);
		$backend->injectValidatorResolver($mockValidatorResolver);
		$backend->_set('classSchemata', array($fullClassName => $classSchema));
		$backend->_set('visitedDuringPersistence', new \SplObjectStorage());
		$backend->_call('persistObject', $oldObject);
	}

}

?>