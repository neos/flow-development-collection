<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Object;

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
 * @testcase for the object serializer
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectSerializerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySerializesTheCorrectPropertyArrayUnderTheCorrectObjectName() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $privateProperty = \'privateProperty\';
			protected $protectedProperty = \'protectedProperty\';
			public $publicProperty = \'publicProperty\';
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('privateProperty', 'protectedProperty', 'publicProperty')));

		$objectSerializer = new \F3\FLOW3\Object\ObjectSerializer($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$objectSerializer->injectReflectionService($mockReflectionService);

		$someObject = new $className();
		$expectedPropertyArray = array(
		  \spl_object_hash($someObject) => array(
			ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'privateProperty' => array (
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'privateProperty',
					),
					'protectedProperty' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'protectedProperty',
					),
					'publicProperty' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'publicProperty',
					)
				)
			)
		);

		$this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The object was not serialized correctly as property array.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySerializesArrayPropertiesCorrectly() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $arrayProperty = array(1,2,3);
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('arrayProperty')));

		$objectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array('buildStorageArrayForArrayProperty'), array(), '', FALSE);
		$objectSerializer->injectReflectionService($mockReflectionService);

		$objectSerializer->expects($this->once())->method('buildStorageArrayForArrayProperty')->with(array(1,2,3))->will($this->returnValue('storable array'));

		$someObject = new $className();
		$expectedPropertyArray = array(
			\spl_object_hash($someObject) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'arrayProperty' => array(
						ObjectSerializer::TYPE => 'array',
						ObjectSerializer::VALUE => 'storable array',
					)
				)
			)
		);

		$this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The array property was not serialized correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySerializesArrayObjectPropertiesCorrectly() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $arrayObjectProperty;

			public function __construct() {
				$this->arrayObjectProperty = new \ArrayObject(array(1,2,3));
			}
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('arrayObjectProperty')));

		$objectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array('buildStorageArrayForArrayProperty'), array(), '', FALSE);
		$objectSerializer->injectReflectionService($mockReflectionService);

		$objectSerializer->expects($this->once())->method('buildStorageArrayForArrayProperty')->with(array(1,2,3))->will($this->returnValue('storable array'));

		$someObject = new $className();
		$expectedPropertyArray = array(
			\spl_object_hash($someObject) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'arrayObjectProperty' => array(
						ObjectSerializer::TYPE => 'ArrayObject',
						ObjectSerializer::VALUE => 'storable array',
					)
				)
			)
		);

		$this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The ArrayObject property was not serialized correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySerializesObjectPropertiesCorrectly() {
		$className1 = uniqid('DummyClass1');
		$className2 = uniqid('DummyClass2');
		eval('class ' . $className2 . '{}');

		eval('class ' . $className1 . ' {
			private $objectProperty;

			public function __construct() {
				$this->objectProperty = new ' . $className2 . '();
			}

			public function getObjectProperty() {
				return $this->objectProperty;
			}
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className1)->will($this->returnValue(array('objectProperty')));
		$mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->with($className2)->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with($className2)->will($this->returnValue('objectName2'));
		$mockObjectManager->expects($this->once())->method('getScope')->with('objectName2')->will($this->returnValue(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE));

		$objectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'), array(), '', FALSE);
		$objectSerializer->injectReflectionService($mockReflectionService);
		$objectSerializer->injectObjectManager($mockObjectManager);

		$someObject = new $className1();

		$expectedPropertyArray = array(
			\spl_object_hash($someObject) => array(
				ObjectSerializer::CLASSNAME => $className1,
				ObjectSerializer::PROPERTIES => array(
					'objectProperty' => array(
						ObjectSerializer::TYPE => 'object',
						ObjectSerializer::VALUE => spl_object_hash($someObject->getObjectProperty()),
					)
				)
			),
			spl_object_hash($someObject->getObjectProperty()) => array(
				ObjectSerializer::CLASSNAME => $className2,
				ObjectSerializer::PROPERTIES => array(),
			)
		);

		$this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The object property was not serialized correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySkipsObjectPropertiesThatAreScopeSingleton() {
		$propertyClassName1 = uniqid('DummyClass');
		$propertyClassName2 = uniqid('DummyClass');
		$propertyClassName3 = uniqid('DummyClass');
		eval('class ' . $propertyClassName1 . ' {}');
		eval('class ' . $propertyClassName2 . ' {}');
		eval('class ' . $propertyClassName3 . ' {}');

		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $property1;
			private $property2;
			private $property3;

			public function __construct() {
				$this->property1 = new ' . $propertyClassName1 . '();
				$this->property2 = new ' . $propertyClassName2 . '();
				$this->property3 = new ' . $propertyClassName3 . '();
			}

			public function getProperty1() {
				return $this->property1;
			}

			public function getProperty3() {
				return $this->property3;
			}
		}');

		$object = new $className();

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('property1', 'property2', 'property3')));
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObjectNameByClassName')->with($propertyClassName1)->will($this->returnValue('propertyObjectName1'));
		$mockObjectManager->expects($this->at(1))->method('getScope')->with('propertyObjectName1')->will($this->returnValue(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE));
		$mockObjectManager->expects($this->at(2))->method('getObjectNameByClassName')->with($propertyClassName2)->will($this->returnValue('propertyObjectName2'));
		$mockObjectManager->expects($this->at(3))->method('getScope')->with('propertyObjectName2')->will($this->returnValue(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON));
		$mockObjectManager->expects($this->at(4))->method('getObjectNameByClassName')->with($propertyClassName3)->will($this->returnValue('propertyObjectName3'));
		$mockObjectManager->expects($this->at(5))->method('getScope')->with('propertyObjectName3')->will($this->returnValue(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION));

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'), array(), '', FALSE);
		$objectSerializer->injectReflectionService($mockReflectionService);
		$objectSerializer->injectObjectManager($mockObjectManager);
		$objectSerializer->_set('objects', array($className => $object));

		$objectHash1 = spl_object_hash($object->getProperty1());
		$objectHash3 = spl_object_hash($object->getProperty3());
		$expectedArray = array(
			spl_object_hash($object) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'property1' => array(
						ObjectSerializer::TYPE => 'object',
						ObjectSerializer::VALUE => $objectHash1,
					),
					'property3' => array(
						ObjectSerializer::TYPE => 'object',
						ObjectSerializer::VALUE => $objectHash3,
					)
				)
			),
			$objectHash1 => array(
				ObjectSerializer::CLASSNAME => $propertyClassName1,
				ObjectSerializer::PROPERTIES => array(),
			),
			$objectHash3 => array(
				ObjectSerializer::CLASSNAME => $propertyClassName3,
				ObjectSerializer::PROPERTIES => array(),
			)
		);

		$this->assertEquals($expectedArray, $objectSerializer->serializeObjectAsPropertyArray($object), 'The singleton has not been skipped.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySkipsPropertiesThatAreAnnotatedToBeTransient() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $privateProperty = \'privateProperty\';
			protected $protectedProperty = \'protectedProperty\';
			public $publicProperty = \'publicProperty\';
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('privateProperty', 'protectedProperty', 'publicProperty')));
		$mockReflectionService->expects($this->at(1))->method('isPropertyTaggedWith')->with($className, 'privateProperty', 'transient')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(2))->method('isPropertyTaggedWith')->with($className, 'protectedProperty', 'transient')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->at(3))->method('isPropertyTaggedWith')->with($className, 'publicProperty', 'transient')->will($this->returnValue(FALSE));

		$objectSerializerClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\ObjectSerializer');
		$objectSerializer = new $objectSerializerClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$objectSerializer->injectReflectionService($mockReflectionService);

		$someObject = new $className();
		$expectedPropertyArray = array(
			spl_object_hash($someObject) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'privateProperty' => array (
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'privateProperty',
					),
					'publicProperty' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'publicProperty',
					)
				)
			)
		);

		$this->assertEquals($expectedPropertyArray, $objectSerializer->serializeObjectAsPropertyArray($someObject), 'The object was not stored correctly as property array.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function serializeObjectSerializesObjectInstancesOnlyOnceToPreventRecursion() {
		$className = uniqid('DummyClassForRecursion');
		eval('class ' . $className . ' {
			public $name;
			public $parent;
			public $child;
		}');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getObjectNameByClassName')->with($className)->will($this->returnValue(ObjectSerializer::CLASSNAME));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('name', 'parent', 'child')));
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->will($this->returnValue(FALSE));

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'));
		$objectSerializer->injectObjectManager($mockObjectManager);
		$objectSerializer->injectReflectionService($mockReflectionService);

		$objectA = new $className();
		$objectA->name = 'A';
		$objectB = new $className();
		$objectB->name = 'B';

		$objectA->child = $objectB;
		$objectB->parent = $objectA;

		$expectedPropertyArray = array(
			spl_object_hash($objectB) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'name' => array (
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'B',
					),
					'parent' => array (
						ObjectSerializer::TYPE => 'object',
						ObjectSerializer::VALUE => \spl_object_hash($objectA),
					),
					'child' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => NULL,
					)
				)
			),
			spl_object_hash($objectA) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'name' => array (
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 'A',
					),
					'parent' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => NULL,
					),
					'child' => array (
						ObjectSerializer::TYPE => 'object',
						ObjectSerializer::VALUE => \spl_object_hash($objectB),
					)
				)
			)
		);

		$actualPropertyArray = $objectSerializer->serializeObjectAsPropertyArray($objectA);
		$this->assertEquals($expectedPropertyArray, $actualPropertyArray);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySerializesOnlyTheUuidOfEntityObjectsIfTheyAreNotMarkedAsNew() {
		$sessionClassName = uniqid('dummyClass');
		eval('class ' . $sessionClassName . ' {
			public $entityProperty;
		}');

		$entityClassName = uniqid('entityClass');
		eval('class ' . $entityClassName . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface, \F3\FLOW3\AOP\ProxyInterface {
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); }
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) {}
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue) {}
			public function FLOW3_Persistence_isNew() {}
			public function FLOW3_Persistence_isClone() {}
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function __clone() {}
		}');

		$entityObject = new $entityClassName();

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->once())->method('isNewObject')->with($entityObject)->will($this->returnValue(FALSE));
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($entityObject)->will($this->returnValue('someUUID'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($sessionClassName)->will($this->returnValue(array('entityProperty')));
		$mockReflectionService->expects($this->at(2))->method('isClassTaggedWith')->with($entityClassName, 'entity')->will($this->returnValue(TRUE));

		$objectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'), array(), '', FALSE);
		$objectSerializer->injectReflectionService($mockReflectionService);
		$objectSerializer->injectPersistenceManager($mockPersistenceManager);

		$expectedArray = array(
			ObjectSerializer::CLASSNAME => $sessionClassName,
			ObjectSerializer::PROPERTIES => array(
				'entityProperty' => array(
					ObjectSerializer::TYPE => 'persistenceObject',
					ObjectSerializer::VALUE => $entityClassName . ':' . 'someUUID',
				)
			)
		);

		$sessionObject = new $sessionClassName();
		$sessionObject->entityProperty = $entityObject;

		$objectsAsArray = $objectSerializer->serializeObjectAsPropertyArray($sessionObject);
		$this->assertEquals($expectedArray, $objectsAsArray[spl_object_hash($sessionObject)]);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArraySerializessOnlyTheUuidOfPersistenceValueobjectsIfTheyAreNotMarkedAsNew() {
		$sessionClassName = uniqid('dummyClass');
		eval('class ' . $sessionClassName . ' {
			public $entityProperty;
		}');

		$entityClassName = uniqid('entityClass');
		eval('class ' . $entityClassName . ' implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface, \F3\FLOW3\AOP\ProxyInterface {
			public function FLOW3_AOP_Proxy_construct() {}
			public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); }
			public function FLOW3_AOP_Proxy_hasProperty($propertyName) {}
			public function FLOW3_AOP_Proxy_getProperty($propertyName) {}
			public function FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue) {}
			public function FLOW3_Persistence_isNew() {}
			public function FLOW3_Persistence_isClone() {}
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function __clone() {}
		}');

		$entityObject = new $entityClassName();

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->once())->method('isNewObject')->with($entityObject)->will($this->returnValue(FALSE));
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($entityObject)->will($this->returnValue('someUUID'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($sessionClassName)->will($this->returnValue(array('entityProperty')));
		$mockReflectionService->expects($this->at(2))->method('isClassTaggedWith')->with($entityClassName, 'entity')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(3))->method('isClassTaggedWith')->with($entityClassName, 'valueobject')->will($this->returnValue(TRUE));

		$objectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'), array(), '', FALSE);
		$objectSerializer->injectReflectionService($mockReflectionService);
		$objectSerializer->injectPersistenceManager($mockPersistenceManager);

		$expectedArray = array(
			ObjectSerializer::CLASSNAME => $sessionClassName,
			ObjectSerializer::PROPERTIES => array(
				'entityProperty' => array(
					ObjectSerializer::TYPE => 'persistenceObject',
					ObjectSerializer::VALUE => $entityClassName . ':someUUID'
				)
			)
		);

		$sessionObject = new $sessionClassName();
		$sessionObject->entityProperty = $entityObject;

		$objectsAsArray = $objectSerializer->serializeObjectAsPropertyArray($sessionObject);
		$this->assertEquals($expectedArray, $objectsAsArray[spl_object_hash($sessionObject)]);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function deserializeObjectsArraySetsTheInternalObjectsAsArrayPropertyCorreclty() {
		$someDataArray = array(
			'bla' => 'blub',
			'another' => 'bla',
			'and another' => 'blub'
		);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(FALSE));

		$objectSerializerClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\ObjectSerializer');
		$objectSerializer = new $objectSerializerClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$objectSerializer->injectObjectManager($mockObjectManager);

		$objectSerializer->deserializeObjectsArray($someDataArray);
		$this->assertEquals($someDataArray, $objectSerializer->_get('objectsAsArray'), 'The data array has not been set as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function deserializeObjectsArrayCallsReconstituteObjectWithTheCorrectObjectData() {
		$className = uniqid('dummyClass');
		eval('class ' . $className . ' {}');

		$className1 = uniqid('class1');
		$object1 = $this->getMock($className, array(), array(), $className1, FALSE);
		$className2 = uniqid('class2');
		$object2 = $this->getMock($className, array(), array(), $className2, FALSE);
		$className3 = uniqid('class3');
		$object3 = $this->getMock($className, array(), array(), $className3, FALSE);

		$objectsAsArray = array(
			spl_object_hash($object1) => array(
				ObjectSerializer::CLASSNAME => $className1,
				ObjectSerializer::PROPERTIES => array(1),
			),
			spl_object_hash($object2) => array(
				ObjectSerializer::CLASSNAME => $className2,
				ObjectSerializer::PROPERTIES => array(2),
			),
			'someReferencedObject1' => array(),
			spl_object_hash($object3) => array(
				ObjectSerializer::CLASSNAME => $className3,
				ObjectSerializer::PROPERTIES => array(3),
			),
			'someReferencedObject2' => array(),
			'someReferencedObject3' => array(),
		);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(TRUE));

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('reconstituteObject', 'createEmptyObject'), array(), '', FALSE);
		$objectSerializer->expects($this->at(0))->method('reconstituteObject')->with(spl_object_hash($object1), $objectsAsArray[spl_object_hash($object1)])->will($this->returnValue($object1));
		$objectSerializer->expects($this->at(1))->method('reconstituteObject')->with(spl_object_hash($object2), $objectsAsArray[spl_object_hash($object2)])->will($this->returnValue($object2));
		$objectSerializer->expects($this->at(2))->method('reconstituteObject')->with(spl_object_hash($object3), $objectsAsArray[spl_object_hash($object3)])->will($this->returnValue($object3));

		$objectSerializer->injectObjectManager($mockObjectManager);

		$objects = $objectSerializer->deserializeObjectsArray($objectsAsArray);

		$this->assertEquals(
			array(
				spl_object_hash($object1) => $object1,
				spl_object_hash($object2) => $object2,
				spl_object_hash($object3) => $object3
			),
			$objects
		);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildStorageArrayCreatesTheCorrectArrayForAnArrayProperty() {
		$objectSerializerClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\ObjectSerializer');
		$objectSerializer = new $objectSerializerClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));

		$expectedArray = array(
			'key1' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 1,
			),
			'key2' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 2,
			),
			'key3' => array(
				ObjectSerializer::TYPE => 'array',
				ObjectSerializer::VALUE => array(
					'key4' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 4,
					),
					'key5' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 5,
					)
				)
			)
		);

		$arrayProperty = array(
			'key1' => 1,
			'key2' => 2,
			'key3' => array(
				'key4' => 4,
				'key5' => 5
			)
		);

		$this->assertSame($expectedArray, $objectSerializer->_call('buildStorageArrayForArrayProperty', $arrayProperty));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildStorageArrayCreatesTheCorrectArrayForAnArrayPropertyWithContainingObject() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);
		$objectName = spl_object_hash($mockObject);

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('serializeObjectAsPropertyArray'), array(), '', FALSE);
		$objectSerializer->expects($this->once())->method('serializeObjectAsPropertyArray')->with($mockObject);

		$arrayProperty = array(
			'key1' => 1,
			'key2' => $mockObject,
		);

		$expectedArray = array(
			'key1' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 1,
			),
			'key2' => array(
				ObjectSerializer::TYPE => 'object',
				ObjectSerializer::VALUE => $objectName,
			)
		);

		$this->assertSame($expectedArray, $objectSerializer->_call('buildStorageArrayForArrayProperty', $arrayProperty));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArrayForSplObjectStoragePropertyBuildsTheCorrectArrayStructureAndStoresEveryObjectInsideSeparately() {
		$propertyClassName1 = uniqid('DummyClass');
		$propertyClassName2 = uniqid('DummyClass');
		eval('class ' . $propertyClassName1 . ' {}');
		eval('class ' . $propertyClassName2 . ' {}');
		$propertyClass1 = new $propertyClassName1();
		$propertyClass2 = new $propertyClassName2();

		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $SplObjectProperty;

			public function __construct($object1, $object2) {
				$this->SplObjectProperty = new \SplObjectStorage();
				$this->SplObjectProperty->attach($object1);
				$this->SplObjectProperty->attach($object2);
			}

			public function getSplObjectProperty() {
				return $this->SplObjectProperty;
			}
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('SplObjectProperty')));
		$mockReflectionService->expects($this->at(1))->method('getClassPropertyNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->at(3))->method('getClassPropertyNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->with($className, 'SplObjectProperty', 'transient')->will($this->returnValue(FALSE));

		$objectSerializer = new \F3\FLOW3\Object\ObjectSerializer($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$objectSerializer->injectReflectionService($mockReflectionService);

		$objectHash1 = spl_object_hash($propertyClass1);
		$objectHash2 = spl_object_hash($propertyClass2);

		$object = new $className($propertyClass1, $propertyClass2);
		$expectedArray = array(
			spl_object_hash($object) => array(
				ObjectSerializer::CLASSNAME => $className,
				ObjectSerializer::PROPERTIES => array(
					'SplObjectProperty' => array(
						ObjectSerializer::TYPE => 'SplObjectStorage',
						ObjectSerializer::VALUE => array($objectHash1, $objectHash2)
					)
				)
			),
			$objectHash1 => array(
				ObjectSerializer::CLASSNAME => $propertyClassName1,
				ObjectSerializer::PROPERTIES => array(),
			),
			$objectHash2 => array(
				ObjectSerializer::CLASSNAME => $propertyClassName2,
				ObjectSerializer::PROPERTIES => array(),
			),
		);

		$this->assertEquals($expectedArray, $objectSerializer->serializeObjectAsPropertyArray($object));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function serializeObjectAsPropertyArrayUsesFLOW3_AOP_Proxy_getPropertyForAOPProxies() {
		$className = uniqid('AOPProxyClass');
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array(), array(), $className, FALSE);
		$object->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('someProperty')->will($this->returnValue('someValue'));
		$object->expects($this->once())->method('FLOW3_AOP_Proxy_getProxyTargetClassName')->will($this->returnValue('ProxyTargetClassName'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with('ProxyTargetClassName')->will($this->returnValue(array('someProperty')));
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->with('ProxyTargetClassName', 'someProperty', 'transient')->will($this->returnValue(FALSE));

		$objectSerializerClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\ObjectSerializer');
		$objectSerializer = new $objectSerializerClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$objectSerializer->injectReflectionService($mockReflectionService);

		$objectSerializer->serializeObjectAsPropertyArray($object);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectCallsTheCorrectReconstitutePropertyTypeFunctionsAndSetsTheValuesInTheObject() {
		$emptyClassName = uniqid('emptyClass');
		eval('class ' . $emptyClassName . ' {}');
		$emptyObject = new $emptyClassName();

		$className = uniqid('someClass');
		eval('class ' . $className . ' {
			private $simpleProperty;
			private $arrayProperty;
			private $arrayObjectProperty;
			private $objectProperty;
			private $splObjectStorageProperty;
			private $persistenceObjectProperty;

			public function getSimpleProperty() { return $this->simpleProperty; }
			public function getArrayProperty() { return $this->arrayProperty; }
			public function getArrayObjectProperty() { return $this->arrayObjectProperty; }
			public function getObjectProperty() { return $this->objectProperty; }
			public function getSplObjectStorageProperty() { return $this->splObjectStorageProperty; }
			public function getPersistenceObjectProperty() { return $this->persistenceObjectProperty; }
		}');

		$objectData = array(
			ObjectSerializer::CLASSNAME => $className,
			ObjectSerializer::PROPERTIES => array(
				'simpleProperty' => array (
					ObjectSerializer::TYPE => 'simple',
					ObjectSerializer::VALUE => 'simplePropertyValue',
				),
				'arrayProperty' => array (
					ObjectSerializer::TYPE => 'array',
					ObjectSerializer::VALUE => 'arrayPropertyValue',
				),
				'arrayObjectProperty' => array (
					ObjectSerializer::TYPE => 'ArrayObject',
					ObjectSerializer::VALUE => 'arrayObjectPropertyValue',
				),
				'objectProperty' => array (
					ObjectSerializer::TYPE => 'object',
					ObjectSerializer::VALUE => 'emptyClass'
				),
				'splObjectStorageProperty' => array (
					ObjectSerializer::TYPE => 'SplObjectStorage',
					ObjectSerializer::VALUE => 'splObjectStoragePropertyValue',
				),
				'persistenceObjectProperty' => array (
					ObjectSerializer::TYPE => 'persistenceObject',
					ObjectSerializer::VALUE => 'persistenceObjectClassName:persistenceObjectUUID',
				)
			)
		);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('recreate')->with($className)->will($this->returnValue(new $className()));
		$mockObjectManager->expects($this->at(1))->method('recreate')->with('emptyClass')->will($this->returnValue($emptyObject));

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('reconstituteArray', 'reconstituteSplObjectStorage', 'reconstitutePersistenceObject'), array(), '', FALSE);
		$objectSerializer->injectObjectManager($mockObjectManager);
		$objectSerializer->expects($this->at(0))->method('reconstituteArray')->with('arrayPropertyValue')->will($this->returnValue('arrayPropertyValue'));
		$objectSerializer->expects($this->at(1))->method('reconstituteArray')->with('arrayObjectPropertyValue')->will($this->returnValue(array('arrayObjectPropertyValue')));
		$objectSerializer->expects($this->once())->method('reconstituteSplObjectStorage')->with('splObjectStoragePropertyValue')->will($this->returnValue('splObjectStoragePropertyValue'));
		$objectSerializer->expects($this->once())->method('reconstitutePersistenceObject')->with('persistenceObjectClassName', 'persistenceObjectUUID')->will($this->returnValue('persistenceObjectPropertyValue'));

		$objectsAsArray = array(
			'emptyClass' => array(
				ObjectSerializer::CLASSNAME => 'emptyClass',
				ObjectSerializer::PROPERTIES => array(),
			)
		);
		$objectSerializer->_set('objectsAsArray', $objectsAsArray);

		$object = $objectSerializer->_call('reconstituteObject', 'dummyobjecthash', $objectData);

		$this->assertEquals('simplePropertyValue', $object->getSimpleProperty(), 'Simple property was not set as expected.');
		$this->assertEquals('arrayPropertyValue', $object->getArrayProperty(), 'Array property was not set as expected.');
		$this->assertEquals(new \ArrayObject(array('arrayObjectPropertyValue')), $object->getArrayObjectProperty(), 'ArrayObject property was not set as expected.');
		$this->assertEquals($emptyObject, $object->getObjectProperty(), 'Object property was not set as expected.');
		$this->assertEquals('splObjectStoragePropertyValue', $object->getSplObjectStorageProperty(), 'SplObjectStorage property was not set as expected.');
		$this->assertEquals('persistenceObjectPropertyValue', $object->getPersistenceObjectProperty(), 'Persistence object property was not set as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorks() {
		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'), array(), '', FALSE);

		$dataArray = array(
			'key1' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 1,
			),
			'key2' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 2,
			),
			'key3' => array(
				ObjectSerializer::TYPE => 'array',
				ObjectSerializer::VALUE => array(
					'key4' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 4,
					),
					'key5' => array(
						ObjectSerializer::TYPE => 'simple',
						ObjectSerializer::VALUE => 5,
					)
				)
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 2,
			'key3' => array(
				'key4' => 4,
				'key5' => 5
			)
		);

		$this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorksWithObjectsInTheArray() {
		$objectsAsArray = array(
			'some object' => array(
				ObjectSerializer::CLASSNAME => 'some object',
				ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
			)
		);

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('reconstituteObject'), array(), '', FALSE);
		$objectSerializer->expects($this->once())->method('reconstituteObject')->with('some object', array(ObjectSerializer::CLASSNAME => 'some object',ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,))->will($this->returnValue('reconstituted object'));
		$objectSerializer->_set('objectsAsArray', $objectsAsArray);


		$dataArray = array(
			'key1' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 1,
			),
			'key2' => array(
				ObjectSerializer::TYPE => 'object',
				ObjectSerializer::VALUE => 'some object'
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 'reconstituted object',
		);

		$this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorksWithSplObjectStorageInTheArray() {
		$objectsAsArray = array(
			'some object' => array(
				ObjectSerializer::CLASSNAME => 'some object',
				ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
			)
		);

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('reconstituteSplObjectStorage'), array(), '', FALSE);
		$objectSerializer->expects($this->once())->method('reconstituteSplObjectStorage')->with('some object', array(ObjectSerializer::CLASSNAME => 'some object',ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,))->will($this->returnValue('reconstituted object'));
		$objectSerializer->_set('objectsAsArray', $objectsAsArray);


		$dataArray = array(
			'key1' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 1,
			),
			'key2' => array(
				ObjectSerializer::TYPE => 'SplObjectStorage',
				ObjectSerializer::VALUE => 'some object'
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 'reconstituted object',
		);

		$this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorksWithPersistenceObjectsInTheArray() {
		$objectsAsArray = array(
			'some object' => array(
				ObjectSerializer::CLASSNAME => 'some object',
				ObjectSerializer::PROPERTIES => ObjectSerializer::PROPERTIES,
			)
		);

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('reconstitutePersistenceObject'), array(), '', FALSE);
		$objectSerializer->expects($this->once())->method('reconstitutePersistenceObject')->with('persistenceObjectClassName', 'someUUID')->will($this->returnValue('reconstituted object'));
		$objectSerializer->_set('objectsAsArray', $objectsAsArray);

		$dataArray = array(
			'key1' => array(
				ObjectSerializer::TYPE => 'simple',
				ObjectSerializer::VALUE => 1,
			),
			'key2' => array(
				ObjectSerializer::TYPE => 'persistenceObject',
				ObjectSerializer::VALUE => array(
					ObjectSerializer::CLASSNAME => 'persistenceObjectClassName',
					'UUID' => 'someUUID',
				)
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 'reconstituted object',
		);

		$this->assertEquals($expectedArrayProperty, $objectSerializer->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteSplObjectStorageWorks() {
		$mockObject1 = $this->getMock(uniqid('dummyClass1'), array(), array(), '', FALSE);
		$mockObject2 = $this->getMock(uniqid('dummyClass2'), array(), array(), '', FALSE);

		$objectsAsArray = array(
			'some object' => array('object1 data'),
			'some other object' => array('object2 data')
		);

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('reconstituteObject'), array(), '', FALSE);
		$objectSerializer->expects($this->at(0))->method('reconstituteObject')->with('some object', array('object1 data'))->will($this->returnValue($mockObject1));
		$objectSerializer->expects($this->at(1))->method('reconstituteObject')->with('some other object', array('object2 data'))->will($this->returnValue($mockObject2));
		$objectSerializer->_set('objectsAsArray', $objectsAsArray);


		$dataArray = array('some object', 'some other object');

		$expectedResult = new \SplObjectStorage();
		$expectedResult->attach($mockObject1);
		$expectedResult->attach($mockObject2);

		$this->assertEquals($expectedResult, $objectSerializer->_call('reconstituteSplObjectStorage', $dataArray), 'The SplObjectStorage was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstitutePersistenceObjectRetrievesTheObjectCorrectlyFromThePersistenceFramework() {
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('someUUID')->will($this->returnValue('theObject'));

		$objectSerializer = $this->getAccessibleMock('F3\FLOW3\Object\ObjectSerializer', array('dummy'), array(), '', FALSE);
		$objectSerializer->injectPersistenceManager($mockPersistenceManager);
	
		$this->assertEquals('theObject', $objectSerializer->_call('reconstitutePersistenceObject', 'someClassName', 'someUUID'));
	}
}

?>
