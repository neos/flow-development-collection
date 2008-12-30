<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Testcase for the Object Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
		$objectRegistry = new \F3\FLOW3\Object\TransientRegistry();

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($mockReflectionService);
		$objectManager->injectObjectBuilder($objectBuilder);
		$objectManager->injectObjectFactory($objectFactory);
		$objectManager->injectObjectRegistry($objectRegistry);

		$objectManager->initialize();

		$this->assertSame($objectManager, $objectManager->getObject('F3\FLOW3\Object\ManagerInterface'));
		$this->assertSame($mockReflectionService, $objectManager->getObject('F3\FLOW3\Reflection\Service'));
		$this->assertSame($objectBuilder, $objectManager->getObject('F3\FLOW3\Object\Builder'));
		$this->assertSame($objectFactory, $objectManager->getObject('F3\FLOW3\Object\FactoryInterface'));
		$this->assertSame($objectRegistry, $objectManager->getObject('F3\FLOW3\Object\RegistryInterface'));
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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($mockObjectFactory);
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder');
		$mockObjectBuilder->expects($this->once())->method('createObject')->with($className)->will($this->returnValue($expectedObject));

		$mockObjectRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockObjectRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(FALSE));
		$mockObjectRegistry->expects($this->once())->method('putObject')->with($className, $expectedObject);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($mockObjectRegistry);

		$objectManager->registerObject($className);

		$retrievedObject = $objectManager->getObject($className);
		$this->assertSame($expectedObject, $retrievedObject);
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

		$mockObjectRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockObjectRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(FALSE));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($mockObjectRegistry);

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

		$mockObjectRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockObjectRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(TRUE));
		$mockObjectRegistry->expects($this->once())->method('getObject')->with($className)->will($this->returnValue($expectedObject));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($mockObjectRegistry);

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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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
			->expects($this->exactly(2))
			->method('isClassTaggedWith')
			->will($this->returnValue(TRUE));

		$mockReflectionService
			->expects($this->exactly(2))
			->method('getClassTagValues')
			->will($this->onConsecutiveCalls(array('singleton'), array('prototype')));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($mockReflectionService);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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

		$mockRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockRegistry->expects($this->once())->method('putObject')->with($mockClassName, $mockObject);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($mockRegistry);

		$objectManager->registerObject($mockClassName, NULL, $mockObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\InvalidClass
	 */
	public function registerObjectRejectsAbstractClasses() {
		$className = 'AbstractClass' . uniqid();
		eval('abstract class ' . $className . ' {}');

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

		$objectManager->registerObject($className);
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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

		$objectManager->registerObject($className);
		$this->assertTrue($objectManager->isObjectRegistered($className));
		$objectManager->unregisterObject($className);
		$this->assertFalse($objectManager->isObjectRegistered($className));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unregisterObjectAlsoRemovesTheObjectInstanceFromTheObjectRegistryIfOneExists() {
		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$mockObjectRegistry = $this->getMock('F3\FLOW3\Object\RegistryInterface');
		$mockObjectRegistry->expects($this->once())->method('objectExists')->with($className)->will($this->returnValue(TRUE));
		$mockObjectRegistry->expects($this->once())->method('removeObject')->with($className);

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectRegistry($mockObjectRegistry);

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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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
		$objectManager->setObjectConfigurations(array('F3\TestPackage\BasicClass' => 'Some string'));
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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

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
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectObjectRegistry($this->getMock('F3\FLOW3\Object\RegistryInterface'));

		$objectManager->registerObject($className1);
		$objectManager->registerObject($className2);

		$registeredObjects = $objectManager->getRegisteredObjects();
		$this->assertTrue(is_array($registeredObjects), 'The result is not an array.');

		foreach ($registeredObjects as $mixedCase => $lowerCase) {
			$this->assertTrue(strlen($mixedCase) > 0, 'The object name was an empty string.');
			$this->assertTrue(strtolower($mixedCase) === $lowerCase, 'The key and value were not equal after strtolower().');
		}
	}
}
?>