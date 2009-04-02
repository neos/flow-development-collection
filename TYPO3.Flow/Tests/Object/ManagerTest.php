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
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

/**
 * Testcase for the Object Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultContextIsDevelopment() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$objectManager = new \F3\FLOW3\Object\Manager($mockReflectionService);
		$this->assertEquals('Development', $objectManager->getContext(), 'getContext() did not return "Development".');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContextAllowsForSettingTheContext() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$objectManager = new \F3\FLOW3\Object\Manager($mockReflectionService);
		$objectManager->setContext('halululu');
		$this->assertEquals('halululu', $objectManager->getContext(), 'getContext() did not return the context we set.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeRegistersTheObjectManagerAndAllInjectedDependenciesAsManagedObjects() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE, FALSE);
		$objectBuilder = new \F3\FLOW3\Object\Builder();
		$objectFactory = new \F3\FLOW3\Object\Factory();
		$singletonObjectsRegistry = new \F3\FLOW3\Object\TransientRegistry();

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($objectBuilder);
		$objectManager->injectObjectFactory($objectFactory);
		$objectManager->injectSingletonObjectsRegistry($singletonObjectsRegistry);
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->initialize();

		$this->assertSame($objectManager, $objectManager->getObject('F3\FLOW3\Object\ManagerInterface'));
		$this->assertSame($mockReflectionService, $objectManager->getObject('F3\FLOW3\Reflection\Service'));
		$this->assertSame($objectBuilder, $objectManager->getObject('F3\FLOW3\Object\Builder'));
		$this->assertSame($objectFactory, $objectManager->getObject('F3\FLOW3\Object\FactoryInterface'));
		$this->assertSame($singletonObjectsRegistry, $objectManager->getObject('F3\FLOW3\Object\RegistryInterface'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectFactoryAlwaysReturnsTheSameObjectFactory() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$objectManager = new \F3\FLOW3\Object\Manager($mockReflectionService);
		$objectFactory1 = $objectManager->getObjectFactory();
		$objectFactory2 = $objectManager->getObjectFactory();
		$this->assertSame($objectFactory1, $objectFactory2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObject
	 */
	public function getObjectThrowsAnExceptionIfTheSpecifiedObjectIsNotRegistered() {
		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->getObject('ThisObjectNameHasCertainlyNotBeenRegistered');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectCallsTheObjectFactoryInOrderToCreateANewPrototypeObject() {
		$expectedObject = new \ArrayObject();
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->will($this->returnValue($expectedObject));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($mockObjectFactory);
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\Foo\Bar\Fixture\Object');
		$objectConfiguration->setScope(\F3\FLOW3\Object\Configuration::SCOPE_PROTOTYPE);
		$objectManager->setObjectConfiguration($objectConfiguration);

		$retrievedObject = $objectManager->getObject('F3\Foo\Bar\Fixture\Object');
		$this->assertSame($expectedObject, $retrievedObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectCreatesANewInstanceOfSingletonObjectsAndStoresThemInTheRegistryIfAnInstanceDoesntExistYet() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$expectedObject = new $className();

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder');
		$mockObjectBuilder->expects($this->once())->method('createObject')->with($className)->will($this->returnValue($expectedObject));

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockSingletonObjectsRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(FALSE));
		$mockSingletonObjectsRegistry->expects($this->at(1))->method('putObject');
		$mockSingletonObjectsRegistry->expects($this->at(2))->method('putObject')->with($className, $expectedObject);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($mockSingletonObjectsRegistry);
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObject($className);

		$retrievedObject = $objectManager->getObject($className);
		$this->assertSame($expectedObject, $retrievedObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifGetObjectCreatesANewInstanceOfSingletonObjectsItAddsThemToListOfShutdownObjectsIfNecessary() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$expectedObject = new $className();

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder');
		$mockObjectBuilder->expects($this->once())->method('createObject')->with($className)->will($this->returnValue($expectedObject));

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockSingletonObjectsRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(FALSE));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($mockSingletonObjectsRegistry);
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObject($className);

		$retrievedObject = $objectManager->getObject($className);

		$shutdownObjectsReflection = new \ReflectionProperty($objectManager, 'shutdownObjects');
		$shutdownObjectsReflection->setAccessible(TRUE);
		$shutdownObjects = $shutdownObjectsReflection->getValue($objectManager);

		$expectedArray = array(
			spl_object_hash($expectedObject) => array($expectedObject, 'shutdownObject')
		);

		$this->assertSame($expectedArray, $shutdownObjects);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectPassesAdditionalArgumentsToTheObjectBuilder() {
		$someObject = new \ArrayObject();
		$arguments = array(
			1 => new \F3\FLOW3\Object\ConfigurationArgument(1, 'arg1', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			2 => new \F3\FLOW3\Object\ConfigurationArgument(2, $someObject, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);

		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$objectConfiguration = new \F3\FLOW3\Object\Configuration($className);
		$objectConfiguration->setConfigurationSourceHint('F3\FLOW3\Object\Manager');

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder');
		$mockObjectBuilder->expects($this->once())->method('createObject')->with($className, $objectConfiguration, $arguments)->will($this->returnValue(new $className));

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockSingletonObjectsRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(FALSE));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($mockSingletonObjectsRegistry);
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className);
		$objectManager->getObject($className, 'arg1', $someObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectReturnsSingletonObjectsFromTheRegistryIfAnInstanceAlreadyExists() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');
		$expectedObject = new $className;

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockSingletonObjectsRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(TRUE));
		$mockSingletonObjectsRegistry->expects($this->once())->method('getObject')->with($className)->will($this->returnValue($expectedObject));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($mockSingletonObjectsRegistry);
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className);

		$retrievedObject = $objectManager->getObject($className);
		$this->assertSame($expectedObject, $retrievedObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectRegistersTheGivenObjectNameForFurtherUsage() {
		$mockObject = $this->getMock('stdclass');
		$mockClassName = get_class($mockObject);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$this->assertEquals($objectManager->isObjectRegistered($mockClassName), FALSE, 'isObjectRegistered() did not return FALSE although object is not yet registered.');
		$objectManager->registerObject($mockClassName);
		$this->assertTrue($objectManager->isObjectRegistered($mockClassName), 'isObjectRegistered() did not return TRUE although object has been registered.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectAssumesThatTheClassNameEqualsTheGivenObjectNameIfNoClassNameIsSpecified() {
		$mockObject = $this->getMock('stdclass');
		$mockClassName = get_class($mockObject);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($mockClassName);
		$objectConfiguration = $objectManager->getObjectConfiguration($mockClassName);
		$this->assertSame($mockClassName, $objectConfiguration->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectAssignsTheClassNameToTheObjectConfigurationIfOneWasSpecified() {
		$mockObject = $this->getMock('stdclass');
		$mockClassName = get_class($mockObject);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject('F3\Foo:Bar\MockObjectName', $mockClassName);
		$objectConfiguration = $objectManager->getObjectConfiguration('F3\Foo:Bar\MockObjectName');
		$this->assertSame($mockClassName, $objectConfiguration->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectDetectsScopeAnnotationsAndSetsTheScopeInItsObjectConfigurationAccordingly() {
		$className1 = 'SomeClass' . uniqid();
		eval('class ' . $className1 . ' {}');

		$className2 = 'SomeClass' . uniqid();
		eval('class ' . $className2 . ' {}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService
			->expects($this->any())
			->method('isInitialized')
			->will($this->returnValue(TRUE));

		$mockReflectionService
			->expects($this->any())
			->method('isClassTaggedWith')
			->will($this->returnValue(TRUE));

		$mockReflectionService
			->expects($this->exactly(2))
			->method('getClassTagValues')
			->will($this->onConsecutiveCalls(array('singleton'), array('prototype')));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObject($className1);
		$objectManager->registerObject($className2);

		$objectConfiguration1 = $objectManager->getObjectConfiguration($className1);
		$objectConfiguration2 = $objectManager->getObjectConfiguration($className2);

		$this->assertEquals(\F3\FLOW3\Object\Configuration::SCOPE_SINGLETON, $objectConfiguration1->getScope());
		$this->assertEquals(\F3\FLOW3\Object\Configuration::SCOPE_PROTOTYPE, $objectConfiguration2->getScope());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectPutsTheObjectIntoTheRegistryIfOneWasGiven() {
		$mockObject = $this->getMock('stdclass');
		$mockClassName = get_class($mockObject);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');

		$mockRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockRegistry->expects($this->at(0))->method('putObject')->with('F3\FLOW3\Reflection\Service', $mockReflectionService);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($mockRegistry);
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObject($mockClassName, NULL, $mockObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectTypeAutomaticallySetsTheClassNameOfTheDefaultImplementationIfTheReflectionServiceFindsOne() {
		$interfaceName = 'SomeInterface' . uniqid();
		eval('interface ' . $interfaceName . ' {}');

		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' implements ' . $interfaceName . ' {}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService
			->expects($this->once())
			->method('getDefaultImplementationClassNameForInterface')
			->with($interfaceName)
			->will($this->returnValue($className));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObjectType($interfaceName);
		$this->assertSame($className, $objectManager->getObjectConfiguration($interfaceName)->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectTypeDetectsTheScopeAnnotationOfTheDefaultImplementationClassAndSetsTheScopeInItsObjectConfigurationAccordingly() {
		$interfaceName = 'SomeInterface' . uniqid();
		eval('interface ' . $interfaceName . ' {}');

		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' implements ' . $interfaceName . ' {}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService
			->expects($this->once())
			->method('getDefaultImplementationClassNameForInterface')
			->with($interfaceName)
			->will($this->returnValue($className));

		$mockReflectionService
			->expects($this->any())
			->method('isInitialized')
			->will($this->returnValue(TRUE));

		$mockReflectionService
			->expects($this->once())
			->method('isClassTaggedWith')
			->will($this->returnValue(TRUE));

		$mockReflectionService
			->expects($this->once())
			->method('getClassTagValues')
			->will($this->returnValue(array('prototype')));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObjectType($interfaceName);
		$objectConfiguration = $objectManager->getObjectConfiguration($interfaceName);
		$this->assertEquals(\F3\FLOW3\Object\Configuration::SCOPE_PROTOTYPE, $objectConfiguration->getScope());
	}


	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unregisterObjectUnregistersPreviouslyRegisteredObjects() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className);
		$this->assertTrue($objectManager->isObjectRegistered($className));
		$objectManager->unregisterObject($className);
		$this->assertFalse($objectManager->isObjectRegistered($className));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unregisterObjectAlsoRemovesTheObjectInstanceFromTheSingletonObjectsRegistryIfOneExists() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockSingletonObjectsRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(TRUE));
		$mockSingletonObjectsRegistry->expects($this->once())->method('removeObject')->with($className);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($mockSingletonObjectsRegistry);
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className);
		$objectManager->unregisterObject($className);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitiveObjectNameReturnsTheMixedCaseObjectNameOfObjectsSpecifiedInArbitraryCase() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className);
		$this->assertSame($className, $objectManager->getCaseSensitiveObjectName(strtolower($className)));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObject
	 */
	public function unregisterObjectThrowsAnExceptionOnTryingToUnregisterNotRegisteredObjects() {
		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->unregisterObject('F3\NonExistentPackage\NonExistentClass');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurationsReplacesAllButOnlyThoseConfigurationsWhichAreSpecified() {
		$objectManager = new \F3\FLOW3\Object\Manager();

		$configuration1 = new \F3\FLOW3\Object\Configuration('Configuration1');
		$configuration2 = new \F3\FLOW3\Object\Configuration('Configuration2');
		$newConfiguration2 = new \F3\FLOW3\Object\Configuration('Configuration2');
		$newConfiguration3 = new \F3\FLOW3\Object\Configuration('Configuration3');

		$objectManager->setObjectConfigurations(array($configuration1, $configuration2));
		$objectManager->setObjectConfigurations(array($newConfiguration2, $newConfiguration3));

		$expectedConfiguration = array(
			'Configuration1' => $configuration1,
			'Configuration2' => $newConfiguration2,
			'Configuration3' => $newConfiguration3
		);
		$this->assertEquals($expectedConfiguration, $objectManager->getObjectConfigurations());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException InvalidArgumentException
	 */
	public function setObjectConfigurationsThrowsAnExceptionIfTheGivenArrayDoesNotContainConfigurationObjects() {
		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->setObjectConfigurations(array('F3\Foo\Bar' => 'Some string'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurationRegistersYetUnknownObjectsFromObjectConfiguration() {
		$objectManager = new \F3\FLOW3\Object\Manager();
		$this->assertFalse($objectManager->isObjectRegistered('Foo'));
		$configuration = new \F3\FLOW3\Object\Configuration('Foo');
		$objectManager->setObjectConfiguration($configuration);
		$this->assertTrue($objectManager->isObjectRegistered('Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfigurationReturnsACloneOfTheOriginalConfigurationObject() {
		$objectManager = new \F3\FLOW3\Object\Manager();

		$originalConfiguration = new \F3\FLOW3\Object\Configuration('Foo');
		$objectManager->setObjectConfiguration($originalConfiguration);

		$this->assertNotSame($originalConfiguration, $objectManager->getObjectConfiguration('Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectClassNameAllowsForConvenientlySettingsTheClassNameOfARegisteredObject() {
		$className1 = 'SomeClass' . uniqid();
		eval('class ' . $className1 . ' {}');

		$className2 = 'SomeClass' . uniqid();
		eval('class ' . $className2 . ' {}');

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className1);
		$objectManager->setObjectClassName($className1, $className2);
		$this->assertSame($className2, $objectManager->getObjectConfiguration($className1)->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredObjectsReturnsAnArrayOfMixedCaseAndLowerCaseObjectNames() {
		$className1 = 'SomeClass' . uniqid();
		eval('class ' . $className1 . ' {}');
		$className2 = 'SomeClass' . uniqid();
		eval('class ' . $className2 . ' {}');

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className1);
		$objectManager->registerObject($className2);

		$registeredObjects = $objectManager->getRegisteredObjects();
		$this->assertTrue(is_array($registeredObjects), 'The result is not an array.');

		foreach ($registeredObjects as $mixedCase => $lowerCase) {
			$this->assertTrue(strlen($mixedCase) > 0, 'The object name was an empty string.');
			$this->assertTrue(strtolower($mixedCase) === $lowerCase, 'The key and value were not equal after strtolower().');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerShutdownObjectPutsAndObjectIntoTheListOfDisposableObjects() {
		$className = 'SomeClass' . uniqid();
		$mockObject = $this->getMock($className);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerShutdownObject($mockObject, 'prepareLastWill');

		$shutdownObjectsReflection = new \ReflectionProperty($objectManager, 'shutdownObjects');
		$shutdownObjectsReflection->setAccessible(TRUE);
		$shutdownObjects = $shutdownObjectsReflection->getValue($objectManager);

		$expectedArray = array(
			spl_object_hash($mockObject) => array($mockObject, 'prepareLastWill')
		);

		$this->assertSame($expectedArray, $shutdownObjects);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownCallsTheShutdownMethodsOfAllObjectsInTheSingletonObjectsRegistry() {
		$className = 'SomeClass' . uniqid();
		$mockObject1 = $this->getMock($className, array('shutdownObject'));
		$mockObject1->expects($this->once())->method('shutdownObject');

		$mockObject2 = $this->getMock($className, array('prepareForSelfDestruction'));
		$mockObject2->expects($this->once())->method('prepareForSelfDestruction');

		$shutdownObjects = array(
			spl_object_hash($mockObject1) => array($mockObject1, 'shutdownObject'),
			spl_object_hash($mockObject2) => array($mockObject2, 'prepareForSelfDestruction')
		);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$shutdownObjectsReflection = new \ReflectionProperty($objectManager, 'shutdownObjects');
		$shutdownObjectsReflection->setAccessible(TRUE);
		$shutdownObjectsReflection->setValue($objectManager, $shutdownObjects);

		$objectManager->shutdown();
	}

}
?>