<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @package FLOW3
 * @version $Id: TransientRegistryTest.php 1838 2009-02-02 13:03:59Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

/**
 * Testcase for the session object registry
 *
 * @package FLOW3
 * @version $Id: TransientRegistryTest.php 1838 2009-02-02 13:03:59Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SessionRegistryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObject
	 */
	public function putObjectThrowsAnExceptionOnInvalidObjects() {
		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject('someClassName', 'no object');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectName
	 */
	public function putObjectThrowsAnExceptionOnInvalidObjectName() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);

		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject('', $mockObject);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function putObjectStoresTheGivenObjectUnderItsNameInMemory() {
		$className1 = uniqid('DummyClass');
		eval('class ' . $className1 . ' {}');
		$className2 = uniqid('DummyClass');
		eval('class ' . $className2 . ' {}');
		$mockObject1 = $this->getMock($className1);
		$mockObject2 = $this->getMock($className2);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject($className1, $mockObject1);
		$sessionRegistry->putObject($className2, $mockObject2);

		$expectedArray = array(
			$className1 => $mockObject1,
			$className2 => $mockObject2,
		);

		$this->assertEquals($expectedArray, $sessionRegistry->_get('objects'), 'Objects were not stored correctly in memory.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayStoresTheCorrectPropertyArrayUnderTheCorrectObjectName() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $privateProperty = \'privateProperty\';
			protected $protectedProperty = \'protectedProperty\';
			public $publicProperty = \'publicProperty\';
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('privateProperty', 'protectedProperty', 'publicProperty')));

		$sessionRegistryClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry');
		$sessionRegistry = new $sessionRegistryClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$expectedPropertyArray = array(
			$className => array(
				'className' => $className,
				'properties' => array(
					'privateProperty' => array (
						'type' => 'simple',
						'value' => 'privateProperty',
					),
					'protectedProperty' => array(
						'type' => 'simple',
						'value' => 'protectedProperty',
					),
					'publicProperty' => array(
						'type' => 'simple',
						'value' => 'publicProperty',
					)
				)
			)
		);

		$someObject = new $className();
		$sessionRegistry->_call('storeObjectAsPropertyArray', $className, $someObject);

		$this->assertEquals($expectedPropertyArray, $sessionRegistry->_get('objectsAsArray'), 'The object was not stored correctly as property array.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayStoresArrayPropertiesCorrectly() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $arrayProperty = array(1,2,3);
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('arrayProperty')));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('buildStorageArrayForArrayProperty'), array(), '', FALSE);
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$sessionRegistry->expects($this->once())->method('buildStorageArrayForArrayProperty')->with(array(1,2,3))->will($this->returnValue('storable array'));

		$someObject = new $className();
		$sessionRegistry->_call('storeObjectAsPropertyArray', $className, $someObject);

		$expectedPropertyArray = array(
			$className => array(
				'className' => $className,
				'properties' => array(
					'arrayProperty' => array(
						'type' => 'array',
						'value' => 'storable array',
					)
				)
			)
		);

		$this->assertEquals($expectedPropertyArray, $sessionRegistry->_get('objectsAsArray'), 'The array property was not stored correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayStoresObjectPropertiesCorrectly() {
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className1)->will($this->returnValue(array('objectProperty')));
		$mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->with($className2)->will($this->returnValue(array()));

		$mockPrototypeObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockPrototypeObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('prototype'));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with($className2)->will($this->returnValue('objectName2'));
		$mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with('objectName2')->will($this->returnValue($mockPrototypeObjectConfiguration));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->injectReflectionService($mockReflectionService);
		$sessionRegistry->injectObjectManager($mockObjectManager);

		$someObject = new $className1();
		$objectHash = spl_object_hash($someObject->getObjectProperty());
		$sessionRegistry->_call('storeObjectAsPropertyArray', $className1, $someObject);

		$expectedPropertyArray = array(
			$className1 => array(
				'className' => $className1,
				'properties' => array(
					'objectProperty' => array(
						'type' => 'object',
						'value' => $objectHash,
					)
				)
			),
			$objectHash => array(
				'className' => $className2,
				'properties' => array(),
			)
		);

		$this->assertEquals($expectedPropertyArray, $sessionRegistry->_get('objectsAsArray'), 'The object property was not stored correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArraySkipsObjectPropertiesThatAreScopeSingleton() {
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('property1', 'property2', 'property3')));
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));

		$mockSingletonObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockSingletonObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('singleton'));
		$mockPrototypeObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockPrototypeObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('prototype'));
		$mockSessionObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockSessionObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('session'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObjectNameByClassName')->with($propertyClassName1)->will($this->returnValue('propertyObjectName1'));
		$mockObjectManager->expects($this->at(1))->method('getObjectConfiguration')->with('propertyObjectName1')->will($this->returnValue($mockPrototypeObjectConfiguration));
		$mockObjectManager->expects($this->at(2))->method('getObjectNameByClassName')->with($propertyClassName2)->will($this->returnValue('propertyObjectName2'));
		$mockObjectManager->expects($this->at(3))->method('getObjectConfiguration')->with('propertyObjectName2')->will($this->returnValue($mockSingletonObjectConfiguration));
		$mockObjectManager->expects($this->at(4))->method('getObjectNameByClassName')->with($propertyClassName3)->will($this->returnValue('propertyObjectName3'));
		$mockObjectManager->expects($this->at(5))->method('getObjectConfiguration')->with('propertyObjectName3')->will($this->returnValue($mockSessionObjectConfiguration));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->injectReflectionService($mockReflectionService);
		$sessionRegistry->injectObjectManager($mockObjectManager);
		$sessionRegistry->_set('objects', array($className => $object));

		$objectHash1 = spl_object_hash($object->getProperty1());
		$objectHash3 = spl_object_hash($object->getProperty3());
		$expectedArray = array(
			$className => array(
				'className' => $className,
				'properties' => array(
					'property1' => array(
						'type' => 'object',
						'value' => $objectHash1,
					),
					'property3' => array(
						'type' => 'object',
						'value' => $objectHash3,
					)
				)
			),
			$objectHash1 => array(
				'className' => $propertyClassName1,
				'properties' => array(),
			),
			$objectHash3 => array(
				'className' => $propertyClassName3,
				'properties' => array(),
			)
		);

		$sessionRegistry->_call('storeObjectAsPropertyArray', $className, $object);

		$this->assertEquals($expectedArray, $sessionRegistry->_get('objectsAsArray'), 'The singleton has not been skipped.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArraySkipsPropertiesThatAreAnnotatedToBeTransient() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {
			private $privateProperty = \'privateProperty\';
			protected $protectedProperty = \'protectedProperty\';
			public $publicProperty = \'publicProperty\';
		}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('privateProperty', 'protectedProperty', 'publicProperty')));
		$mockReflectionService->expects($this->at(1))->method('isPropertyTaggedWith')->with($className, 'privateProperty', 'transient')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(2))->method('isPropertyTaggedWith')->with($className, 'protectedProperty', 'transient')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->at(3))->method('isPropertyTaggedWith')->with($className, 'publicProperty', 'transient')->will($this->returnValue(FALSE));

		$sessionRegistryClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry');
		$sessionRegistry = new $sessionRegistryClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$expectedPropertyArray = array(
			$className => array(
				'className' => $className,
				'properties' => array(
					'privateProperty' => array (
						'type' => 'simple',
						'value' => 'privateProperty',
					),
					'publicProperty' => array(
						'type' => 'simple',
						'value' => 'publicProperty',
					)
				)
			)
		);

		$someObject = new $className();
		$sessionRegistry->_call('storeObjectAsPropertyArray', $className, $someObject);

		$this->assertEquals($expectedPropertyArray, $sessionRegistry->_get('objectsAsArray'), 'The object was not stored correctly as property array.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayStoresOnlyTheUUIDOfEntityObjectsIfTheyAreNotMarkedAsNew() {
		$sessionClassName = uniqid('dummyClass');
		eval('class ' . $sessionClassName . ' {
			public $entityProperty;
		}');

		$entityObject = $this->getMock('F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface', array('FLOW3_Persistence_isNew', 'FLOW3_AOP_Proxy_getProperty', 'FLOW3_Persistence_isDirty', 'FLOW3_Persistence_memorizeCleanState'));
		$entityClassName = get_class($entityObject);
		$entityObject->expects($this->once())->method('FLOW3_Persistence_isNew')->will($this->returnValue(FALSE));
		$entityObject->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('FLOW3_Persistence_Entity_UUID')->will($this->returnValue('someUUID'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($sessionClassName)->will($this->returnValue(array('entityProperty')));
		$mockReflectionService->expects($this->at(2))->method('isClassTaggedWith')->with($entityClassName, 'entity')->will($this->returnValue(TRUE));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$expectedArray = array(
			'className' => $sessionClassName,
			'properties' => array(
				'entityProperty' => array(
					'type' => 'persistenceObject',
					'value' => array(
						'className' => $entityClassName,
						'UUID' => 'someUUID',
					)
				)
			)
		);

		$sessionObject = new $sessionClassName();
		$sessionObject->entityProperty = $entityObject;

		$sessionRegistry->_call('storeObjectAsPropertyArray', 'myObjectName', $sessionObject);

		$objectsAsArray = $sessionRegistry->_get('objectsAsArray');
		$this->assertEquals($expectedArray, $objectsAsArray['myObjectName']);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayStoresOnlyTheUUIDOfPersistenceValueobjectsIfTheyAreNotMarkedAsNew() {
		$sessionClassName = uniqid('dummyClass');
		eval('class ' . $sessionClassName . ' {
			public $entityProperty;
		}');

		$entityObject = $this->getMock('F3\FLOW3\Persistence\Aspect\DirtyMonitoringInterface', array('FLOW3_Persistence_isNew', 'FLOW3_AOP_Proxy_getProperty', 'FLOW3_Persistence_isDirty', 'FLOW3_Persistence_memorizeCleanState'));
		$entityClassName = get_class($entityObject);
		$entityObject->expects($this->once())->method('FLOW3_Persistence_isNew')->will($this->returnValue(FALSE));
		$entityObject->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('FLOW3_Persistence_Entity_UUID')->will($this->returnValue('someUUID'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($sessionClassName)->will($this->returnValue(array('entityProperty')));
		$mockReflectionService->expects($this->at(2))->method('isClassTaggedWith')->with($entityClassName, 'entity')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(3))->method('isClassTaggedWith')->with($entityClassName, 'valueobject')->will($this->returnValue(TRUE));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$expectedArray = array(
			'className' => $sessionClassName,
			'properties' => array(
				'entityProperty' => array(
					'type' => 'persistenceObject',
					'value' => array(
						'className' => $entityClassName,
						'UUID' => 'someUUID',
					)
				)
			)
		);

		$sessionObject = new $sessionClassName();
		$sessionObject->entityProperty = $entityObject;

		$sessionRegistry->_call('storeObjectAsPropertyArray', 'myObjectName', $sessionObject);

		$objectsAsArray = $sessionRegistry->_get('objectsAsArray');
		$this->assertEquals($expectedArray, $objectsAsArray['myObjectName']);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function buildStorageArrayCreatesTheCorrectArrayForAnArrayProperty() {
		$sessionRegistryClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry');
		$sessionRegistry = new $sessionRegistryClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));

		$expectedArray = array(
			'key1' => array(
				'type' => 'simple',
				'value' => 1,
			),
			'key2' => array(
				'type' => 'simple',
				'value' => 2,
			),
			'key3' => array(
				'type' => 'array',
				'value' => array(
					'key4' => array(
						'type' => 'simple',
						'value' => 4,
					),
					'key5' => array(
						'type' => 'simple',
						'value' => 5,
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

		$this->assertSame($expectedArray, $sessionRegistry->_call('buildStorageArrayForArrayProperty', $arrayProperty));
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

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('storeObjectAsPropertyArray'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('storeObjectAsPropertyArray')->with($objectName, $mockObject);

		$arrayProperty = array(
			'key1' => 1,
			'key2' => $mockObject,
		);

		$expectedArray = array(
			'key1' => array(
				'type' => 'simple',
				'value' => 1,
			),
			'key2' => array(
				'type' => 'object',
				'value' => $objectName,
			)
		);

		$this->assertSame($expectedArray, $sessionRegistry->_call('buildStorageArrayForArrayProperty', $arrayProperty));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayForSplObjectStoragePropertyBuildsTheCorrectArrayStructureAndStoresEveryObjectInsideSeparately() {
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

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('SplObjectProperty')));
		$mockReflectionService->expects($this->at(1))->method('getClassPropertyNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->at(2))->method('getClassPropertyNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->at(3))->method('getClassPropertyNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->with($className, 'SplObjectProperty', 'transient')->will($this->returnValue(FALSE));

		$sessionRegistryClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry');
		$sessionRegistry = new $sessionRegistryClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$objectHash1 = spl_object_hash($propertyClass1);
		$objectHash2 = spl_object_hash($propertyClass2);
		$expectedArray = array(
			$className => array(
				'className' => $className,
				'properties' => array(
					'SplObjectProperty' => array(
						'type' => 'SplObjectStorage',
						'value' => array($objectHash1, $objectHash2)
					)
				)
			),
			$objectHash1 => array(
				'className' => $propertyClassName1,
				'properties' => array(),
			),
			$objectHash2 => array(
				'className' => $propertyClassName2,
				'properties' => array(),
			),
		);

		$object = new $className($propertyClass1, $propertyClass2);
		$sessionRegistry->_call('storeObjectAsPropertyArray', $className, $object);

		$this->assertEquals($expectedArray, $sessionRegistry->_get('objectsAsArray'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storeObjectAsPropertyArrayUsesFLOW3_AOP_Proxy_getPropertyForAOPProxies() {
		$className = uniqid('AOPProxyClass');
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array(), array(), $className, FALSE);
		$object->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('someProperty')->will($this->returnValue('someValue'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('someProperty')));
		$mockReflectionService->expects($this->any())->method('isPropertyTaggedWith')->with($className, 'someProperty', 'transient')->will($this->returnValue(FALSE));

		$sessionRegistryClassName = $this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry');
		$sessionRegistry = new $sessionRegistryClassName($this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE));
		$sessionRegistry->injectReflectionService($mockReflectionService);

		$sessionRegistry->_call('storeObjectAsPropertyArray', $className, $object);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function shutdownObjectCallsStoreObjectAsPropertyArrayForEveryRegisteredObjectAndSaveTheResultInTheSession() {
		$className1 = uniqid('DummyClass');
		eval('class ' . $className1 . ' {}');
		$className2 = uniqid('DummyClass');
		eval('class ' . $className2 . ' {}');
		$mockObject1 = $this->getMock($className1);
		$mockObject2 = $this->getMock($className2);

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('putData')->with('F3_FLOW3_Object_SessionRegistry', array(1,2,3,4,5,6));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('storeObjectAsPropertyArray'), array(), '');
		$sessionRegistry->injectSession($mockSession);
		$sessionRegistry->_set('objects', array($className1 => $mockObject1, $className2 => $mockObject2));
		$sessionRegistry->_set('objectsAsArray', array(1,2,3,4,5,6));
		$sessionRegistry->expects($this->at(0))->method('storeObjectAsPropertyArray')->with($className1, $mockObject1);
		$sessionRegistry->expects($this->at(1))->method('storeObjectAsPropertyArray')->with($className2, $mockObject2);

		$sessionRegistry->shutdownObject();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeObjectReallyRemovesTheObjectFromStorage() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject($className, $mockObject);
		$sessionRegistry->removeObject($className);

		$cachedObjects = $sessionRegistry->_get('objects');
		$this->assertFalse(isset($cachedObjects[$className]), 'removeObject() did not really remove the object.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectName
	 */
	public function removeObjectThrowsAnExceptionIfTheObjectDoesntExist() {
		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);

		$sessionRegistry->removeObject(uniqid('DummyClass'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function objectExistsReturnsCorrectResult() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);

		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);

		$this->assertFalse($sessionRegistry->objectExists($className), 'objectExists() did not return FALSE although the object should not exist yet.');
		$sessionRegistry->putObject($className, $mockObject);
		$this->assertTrue($sessionRegistry->objectExists($className), 'objectExists() did not return TRUE although the object should exist.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectName
	 */
	public function getObjectThrowsAnExceptionIfTheObjectDoesntExist() {
		$objectName = uniqid('DummyClass');

		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('objectExists', 'initialize'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('objectExists')->with($objectName)->will($this->returnValue(FALSE));

		$sessionRegistry->getObject($objectName);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObjectReturnsTheCorrectObject() {
		$objectName = uniqid('DummyClass');
		$object = new \stdClass();

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('objectExists', 'initialize'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('objectExists')->with($objectName)->will($this->returnValue(TRUE));
		$sessionRegistry->_set('objects', array($objectName => $object));

		$this->assertSame($object, $sessionRegistry->getObject($objectName));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeRestoresTheObjectsAsArrayPropertyFromTheSession() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getData')->with('F3_FLOW3_Object_SessionRegistry')->will($this->returnValue(array(1,2,3,4)));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(FALSE));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->_set('session', $mockSession);
		$sessionRegistry->_set('objectManager', $mockObjectManager);

		$sessionRegistry->_call('initialize');

		$this->assertEquals(array(1,2,3,4), $sessionRegistry->_get('objectsAsArray'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeCallsReconstituteObjectForAllObjectsThatAreRegisteredInTheObjectManagerAndStoresThemInTheRegistry() {
		$className = uniqid('dummyClass');
		eval('class ' . $className . ' {}');

		$className1 = uniqid('class1');
		$object1 = $this->getMock($className, array(), array(), $className1, FALSE);
		$className2 = uniqid('class2');
		$object2 = $this->getMock($className, array(), array(), $className2, FALSE);
		$className3 = uniqid('class3');
		$object3 = $this->getMock($className, array(), array(), $className3, FALSE);

		$objectsAsArray = array(
			$className1 => array(
				'className' => $className1,
				'properties' => array(1),
			),
			$className2 => array(
				'className' => $className2,
				'properties' => array(2),
			),
			'someReferencedObject1' => array(),
			$className3 => array(
				'className' => $className3,
				'properties' => array(3),
			),
			'someReferencedObject2' => array(),
			'someReferencedObject3' => array(),
		);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('isObjectRegistered')->with($className1)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->at(1))->method('isObjectRegistered')->with($className2)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->at(2))->method('isObjectRegistered')->with('someReferencedObject1')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(3))->method('isObjectRegistered')->with($className3)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->at(4))->method('isObjectRegistered')->with('someReferencedObject2')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(5))->method('isObjectRegistered')->with('someReferencedObject3')->will($this->returnValue(FALSE));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getData')->with('F3_FLOW3_Object_SessionRegistry')->will($this->returnValue($objectsAsArray));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('reconstituteObject', 'createEmptyObject'), array(), '', FALSE);
		$sessionRegistry->expects($this->at(0))->method('reconstituteObject')->with($objectsAsArray[$className1])->will($this->returnValue($object1));
		$sessionRegistry->expects($this->at(1))->method('reconstituteObject')->with($objectsAsArray[$className2])->will($this->returnValue($object2));
		$sessionRegistry->expects($this->at(2))->method('reconstituteObject')->with($objectsAsArray[$className3])->will($this->returnValue($object3));

		$sessionRegistry->_set('session', $mockSession);
		$sessionRegistry->injectObjectManager($mockObjectManager);

		$sessionRegistry->_call('initialize');

		$this->assertEquals(array($className1 => $object1, $className2 => $object2, $className3 => $object3), $sessionRegistry->_get('objects'), 'Reconstituted objects were not stored correctly in the registry.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createEmptyObjectReturnsAnObjectOfTheSpecifiedType() {
		$className = uniqid('dummyClass');
		eval('class ' . $className . ' {}');

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);

		$object = $sessionRegistry->_call('createEmptyObject', $className);
		$this->assertType($className, $object);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function createEmptyObjectPreventsThatTheConstructorOfTheTargetObjectIsCalled() {
		$className = uniqid('dummyClass');
		eval('class ' . $className . ' {
			public $constructorHasBeenCalled = FALSE;
			public function __construct() { $this->constructorHasBeenCalled = TRUE; }
		}');

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);

		$object = $sessionRegistry->_call('createEmptyObject', $className);
		$this->assertFalse($object->constructorHasBeenCalled);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\UnknownClass
	 */
	public function createEmptyObjectThrowsAnExceptionIfTheClassDoesNotExist() {
		$className = uniqid('notExistingClass');

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$object = $sessionRegistry->_call('createEmptyObject', $className);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteObjectCallsTheCorrectReconstitutePropertyTypeFunctionsAndSetsTheValuesInTheObject() {
		$emptyClassName = uniqid('emptyClass');
		eval('class ' . $emptyClassName . ' {}');
		$emptyObject = new $emptyClassName();

		$className = uniqid('someClass');
		eval('class ' . $className . ' {
			private $simpleProperty;
			private $arrayProperty;
			private $objectProperty;
			private $splObjectStorageProperty;
			private $persistenceObjectProperty;

			public function getSimpleProperty() { return $this->simpleProperty; }
			public function getArrayProperty() { return $this->arrayProperty; }
			public function getObjectProperty() { return $this->objectProperty; }
			public function getSplObjectStorageProperty() { return $this->splObjectStorageProperty; }
			public function getPersistenceObjectProperty() { return $this->persistenceObjectProperty; }
		}');

		$objectData = array(
			'className' => $className,
			'properties' => array(
				'simpleProperty' => array (
					'type' => 'simple',
					'value' => 'simplePropertyValue',
				),
				'arrayProperty' => array (
					'type' => 'array',
					'value' => 'arrayPropertyValue',
				),
				'objectProperty' => array (
					'type' => 'object',
					'value' => 'emptyClass'
				),
				'splObjectStorageProperty' => array (
					'type' => 'SplObjectStorage',
					'value' => 'splObjectStoragePropertyValue',
				),
				'persistenceObjectProperty' => array (
					'type' => 'persistenceObject',
					'value' => array(
						'className' => 'persistenceObjectClassName',
						'UUID' => 'persistenceObjectUUID',
					)
				)
			)
		);

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('createEmptyObject', 'reconstituteArray', 'reconstituteSplObjectStorage', 'reconstitutePersistenceObject'), array(), '', FALSE);
		$sessionRegistry->injectObjectBuilder($mockObjectBuilder);
		$sessionRegistry->injectObjectManager($mockObjectManager);
		$sessionRegistry->expects($this->at(0))->method('createEmptyObject')->with($className)->will($this->returnValue(new $className()));
		$sessionRegistry->expects($this->at(2))->method('createEmptyObject')->with('emptyClass')->will($this->returnValue($emptyObject));
		$sessionRegistry->expects($this->once())->method('reconstituteArray')->with('arrayPropertyValue')->will($this->returnValue('arrayPropertyValue'));
		$sessionRegistry->expects($this->once())->method('reconstituteSplObjectStorage')->with('splObjectStoragePropertyValue')->will($this->returnValue('splObjectStoragePropertyValue'));
		$sessionRegistry->expects($this->once())->method('reconstitutePersistenceObject')->with('persistenceObjectClassName', 'persistenceObjectUUID')->will($this->returnValue('persistenceObjectPropertyValue'));

		$objectsAsArray = array(
			'emptyClass' => array(
				'className' => 'emptyClass',
				'properties' => array(),
			)
		);
		$sessionRegistry->_set('objectsAsArray', $objectsAsArray);

		$object = $sessionRegistry->_call('reconstituteObject', $objectData);

		$this->assertEquals('simplePropertyValue', $object->getSimpleProperty(), 'Simple property was not set as expected.');
		$this->assertEquals('arrayPropertyValue', $object->getArrayProperty(), 'Array property was not set as expected.');
		$this->assertEquals($emptyObject, $object->getObjectProperty(), 'Object property was not set as expected.');
		$this->assertEquals('splObjectStoragePropertyValue', $object->getSplObjectStorageProperty(), 'SplObjectStorage property was not set as expected.');
		$this->assertEquals('persistenceObjectPropertyValue', $object->getPersistenceObjectProperty(), 'Persistence object property was not set as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteObjectReinjectsDependencies() {
		$className = uniqid('someClass');
		eval('class ' . $className . ' {}');
		$object = new $className();

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('reinjectDependencies')->with($object, $mockObjectConfiguration);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with($className)->will($this->returnValue('objectName'));
		$mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with('objectName')->will($this->returnValue($mockObjectConfiguration));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('createEmptyObject'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('createEmptyObject')->with($className)->will($this->returnValue($object));
		$sessionRegistry->injectObjectBuilder($mockObjectBuilder);
		$sessionRegistry->injectObjectManager($mockObjectManager);
		$sessionRegistry->_call('reconstituteObject', array('className' => $className, 'properties' => array()));
	}
	
	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteObjectRegistersShutdownObjects() {
		$className = uniqid('someClass');
		eval('class ' . $className . ' {}');
		$object = new $className();

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->any())->method('getLifecycleShutdownMethodName')->will($this->returnValue('shutdownMethodName'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('getObjectNameByClassName')->with($className)->will($this->returnValue('objectName'));
		$mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with('objectName')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectManager->expects($this->once())->method('registerShutdownObject')->with($object, 'shutdownMethodName');

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('createEmptyObject'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('createEmptyObject')->with($className)->will($this->returnValue($object));
		$sessionRegistry->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE));
		$sessionRegistry->injectObjectManager($mockObjectManager);

		$sessionRegistry->_call('reconstituteObject', array('className' => $className, 'properties' => array()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorks() {
		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);

		$dataArray = array(
			'key1' => array(
				'type' => 'simple',
				'value' => 1,
			),
			'key2' => array(
				'type' => 'simple',
				'value' => 2,
			),
			'key3' => array(
				'type' => 'array',
				'value' => array(
					'key4' => array(
						'type' => 'simple',
						'value' => 4,
					),
					'key5' => array(
						'type' => 'simple',
						'value' => 5,
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

		$this->assertEquals($expectedArrayProperty, $sessionRegistry->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorksWithObjectsInTheArray() {
		$objectsAsArray = array(
			'some object' => array(
				'className' => 'some object',
				'properties' => 'properties',
			)
		);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('reconstituteObject'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('reconstituteObject')->with(array('className' => 'some object','properties' => 'properties',))->will($this->returnValue('reconstituted object'));
		$sessionRegistry->_set('objectsAsArray', $objectsAsArray);


		$dataArray = array(
			'key1' => array(
				'type' => 'simple',
				'value' => 1,
			),
			'key2' => array(
				'type' => 'object',
				'value' => 'some object'
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 'reconstituted object',
		);

		$this->assertEquals($expectedArrayProperty, $sessionRegistry->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorksWithSplObjectStorageInTheArray() {
		$objectsAsArray = array(
			'some object' => array(
				'className' => 'some object',
				'properties' => 'properties',
			)
		);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('reconstituteSplObjectStorage'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('reconstituteSplObjectStorage')->with('some object', array('className' => 'some object','properties' => 'properties',))->will($this->returnValue('reconstituted object'));
		$sessionRegistry->_set('objectsAsArray', $objectsAsArray);


		$dataArray = array(
			'key1' => array(
				'type' => 'simple',
				'value' => 1,
			),
			'key2' => array(
				'type' => 'SplObjectStorage',
				'value' => 'some object'
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 'reconstituted object',
		);

		$this->assertEquals($expectedArrayProperty, $sessionRegistry->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteArrayWorksWithPersistenceObjectsInTheArray() {
		$objectsAsArray = array(
			'some object' => array(
				'className' => 'some object',
				'properties' => 'properties',
			)
		);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('reconstitutePersistenceObject'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('reconstitutePersistenceObject')->with('persistenceObjectClassName', 'someUUID')->will($this->returnValue('reconstituted object'));
		$sessionRegistry->_set('objectsAsArray', $objectsAsArray);

		$dataArray = array(
			'key1' => array(
				'type' => 'simple',
				'value' => 1,
			),
			'key2' => array(
				'type' => 'persistenceObject',
				'value' => array(
					'className' => 'persistenceObjectClassName',
					'UUID' => 'someUUID',
				)
			)
		);

		$expectedArrayProperty = array(
			'key1' => 1,
			'key2' => 'reconstituted object',
		);

		$this->assertEquals($expectedArrayProperty, $sessionRegistry->_call('reconstituteArray', $dataArray), 'The array was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstituteSplObjectStorageWorks() {
		$mockObject1 = $this->getMock(uniqid('dummyClass1'), array(), array(), '', FALSE);
		$mockObject2 = $this->getMock(uniqid('dummyClass2'), array(), array(), '', FALSE);

		$objectsAsArray = array(
			'some object' => 'object1 data',
			'some other object' => 'object2 data'
		);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('reconstituteObject'), array(), '', FALSE);
		$sessionRegistry->expects($this->at(0))->method('reconstituteObject')->with('object1 data')->will($this->returnValue($mockObject1));
		$sessionRegistry->expects($this->at(1))->method('reconstituteObject')->with('object2 data')->will($this->returnValue($mockObject2));
		$sessionRegistry->_set('objectsAsArray', $objectsAsArray);


		$dataArray = array('some object', 'some other object');

		$expectedResult = new \SplObjectStorage();
		$expectedResult->attach($mockObject1);
		$expectedResult->attach($mockObject2);

		$this->assertEquals($expectedResult, $sessionRegistry->_call('reconstituteSplObjectStorage', $dataArray), 'The SplObjectStorage was not reconstituted correctly.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function reconstitutePersistenceObjectRetrievesTheObjectCorrectlyFromThePersistenceFramework() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface', array());
		$mockQuery->expects($this->once())->method('withUUID')->with('someUUID')->will($this->returnValue('UUIDQuery'));
		$mockQuery->expects($this->once())->method('matching')->with('UUIDQuery')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('theObject')));

		$mockQueryFactory = $this->getMock('F3\FLOW3\Persistence\QueryFactoryInterface');
		$mockQueryFactory->expects($this->once())->method('create')->with('someClassName')->will($this->returnValue($mockQuery));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->injectQueryFactory($mockQueryFactory);

		$this->assertEquals('theObject', $sessionRegistry->_call('reconstitutePersistenceObject', 'someClassName', 'someUUID'));
	}
}

?>
