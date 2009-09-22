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
 * Testcase for the Object Manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectReflectionServiceRegistersTheObjectAndClassnameCorrectly() {
		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$this->assertEquals($objectManager->_get('registeredObjects'), array('F3\FLOW3\Reflection\Service' => 'f3\flow3\reflection\service'));
		$this->assertEquals($objectManager->_get('registeredClasses'), array('F3\FLOW3\Reflection\Service' => 'F3\FLOW3\Reflection\Service'));
	}

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
	public function initializeManagerRegistersTheObjectManagerAndAllInjectedDependenciesAsManagedObjects() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE, FALSE);

		$objectConfigurations = array(
			'F3\Foo\Object1' => array(
				'scope' => 'prototype'
			),
			'F3\Foo\Object2' => array(
				'className' => 'Foo\Bar'
			)
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array('getConfiguration'), array(), '', FALSE, FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue($objectConfigurations));

		$objectBuilder = new \F3\FLOW3\Object\Builder();
		$objectFactory = new \F3\FLOW3\Object\Factory();
		$singletonObjectsRegistry = new \F3\FLOW3\Object\TransientRegistry();

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($objectBuilder);
		$objectManager->injectObjectFactory($objectFactory);
		$objectManager->injectSingletonObjectsRegistry($singletonObjectsRegistry);
		$objectManager->injectReflectionService($mockReflectionService);
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$objectManager->initializeManager();

		$this->assertSame($objectManager, $objectManager->getObject('F3\FLOW3\Object\ManagerInterface'));
		$this->assertSame($mockReflectionService, $objectManager->getObject('F3\FLOW3\Reflection\Service'));
		$this->assertSame($objectBuilder, $objectManager->getObject('F3\FLOW3\Object\Builder'));
		$this->assertSame($objectFactory, $objectManager->getObject('F3\FLOW3\Object\FactoryInterface'));
		$this->assertSame($singletonObjectsRegistry, $objectManager->getObject('F3\FLOW3\Object\RegistryInterface'));

		$this->assertSame('Foo\Bar', $objectManager->getObjectConfiguration('F3\Foo\Object2')->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectsLoadsObjectConfigurationsFromCacheIfAvailable() {
		$packages = array('Foo' => $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE));

		$mockObjectsConfigurationCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockObjectsConfigurationCache->expects($this->once())->method('has')->with('baseObjectConfigurations')->will($this->returnValue(TRUE));
		$mockObjectsConfigurationCache->expects($this->once())->method('get')->with('baseObjectConfigurations')->will($this->returnValue(array('Foo' => 'Bar')));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('setObjectConfigurations'), array(), '', FALSE);
		$objectManager->injectObjectConfigurationsCache($mockObjectsConfigurationCache);

		$objectManager->expects($this->once())->method('setObjectConfigurations')->with(array('Foo' => 'Bar'));
		$objectManager->initializeObjects($packages);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectsRegistersClassesFromPackagesIfObjectConfigurationIsNotCached() {
		$packages = array('Foo' => $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE));

		$mockObjectsConfigurationCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockObjectsConfigurationCache->expects($this->once())->method('has')->with('baseObjectConfigurations')->will($this->returnValue(FALSE));
		$mockObjectsConfigurationCache->expects($this->once())->method('getClassTag')->will($this->returnValue('class tag'));
		$mockObjectsConfigurationCache->expects($this->once())->method('set')->with('baseObjectConfigurations', array('configurations'), array('class tag'));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('registerAndConfigureAllPackageObjects'), array(), '', FALSE);
		$objectManager->injectObjectConfigurationsCache($mockObjectsConfigurationCache);
		$objectManager->_set('objectConfigurations', array('configurations'));

		$objectManager->expects($this->once())->method('registerAndConfigureAllPackageObjects');
		$objectManager->initializeObjects($packages);

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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->getObject('ThisObjectNameHasCertainlyNotBeenRegistered');
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectCallsTheObjectFactoryInOrderToCreateANewPrototypeObject() {
		$expectedObject = new \ArrayObject();
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->will($this->returnValue($expectedObject));

		$objectManager = new \F3\FLOW3\Object\Manager();
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($mockObjectFactory);
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\Foo\Bar\Fixture\Object');
		$objectConfiguration->setScope(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE);
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

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
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

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
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
	public function getObjectPassesAdditionalArgumentsToTheObjectBuilderWhenItCreatesANewSingletonObject() {
		$someObject = new \ArrayObject();
		$arguments = array(
			1 => new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'arg1', \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			2 => new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, $someObject, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);

		$className = 'SomeClass' . uniqid();
		eval('class ' . $className . ' {}');

		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($className);
		$objectConfiguration->setConfigurationSourceHint('F3\FLOW3\Object\Manager');

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder');
		$mockObjectBuilder->expects($this->once())->method('createObject')->with($className, $objectConfiguration, $arguments)->will($this->returnValue(new $className));

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
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

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObjectCreatesANewInstanceOfSessionObjectsAndStoresItInTheSessionRegistryIfAnInstanceDoesntExistYet() {
		$objectName = 'mySessionObject';
		$object = $this->getMock('mySessionObject');

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->once())->method('getScope')->will($this->returnValue('session'));

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('createObject')->with($objectName, $mockObjectConfiguration, array('overridingArguments'))->will($this->returnValue($object));

		$mockSessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array(), array(), '', FALSE);
		$mockSessionRegistry->expects($this->once())->method('objectExists')->with($objectName)->will($this->returnValue(FALSE));
		$mockSessionRegistry->expects($this->once())->method('putObject')->with($objectName, $object);

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('isObjectRegistered', 'getOverridingArguments', 'registerShutdownObject'), array(), '', FALSE);
		$objectManager->expects($this->once())->method('getOverridingArguments')->will($this->returnValue(array('overridingArguments')));
		$objectManager->expects($this->once())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectSessionObjectsRegistry($mockSessionRegistry);
		$objectManager->_set('objectConfigurations', array($objectName => $mockObjectConfiguration));

		$this->assertEquals($object, $objectManager->getObject($objectName), 'The session object was not returned as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifGetObjectCreatesANewInstanceOfSessionObjectsItAddsThemToListOfShutdownObjectsIfNecessary() {
		$objectName = 'mySessionObject';
		$object = $this->getMock('mySessionObject');
		$shutdownMethodName = 'shutdownMySessionObject';

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('session'));
		$mockObjectConfiguration->expects($this->any())->method('getLifecycleShutdownMethodName')->will($this->returnValue($shutdownMethodName));

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->any())->method('createObject')->will($this->returnValue($object));

		$mockSessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array(), array(), '', FALSE);
		$mockSessionRegistry->expects($this->any())->method('objectExists')->with($objectName)->will($this->returnValue(FALSE));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('isObjectRegistered', 'getOverridingArguments', 'registerShutdownObject'), array(), '', FALSE);
		$objectManager->expects($this->any())->method('getOverridingArguments')->will($this->returnValue(array('overridingArguments')));
		$objectManager->expects($this->any())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$objectManager->expects($this->once())->method('registerShutdownObject')->with($object, $shutdownMethodName);
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectSessionObjectsRegistry($mockSessionRegistry);
		$objectManager->_set('objectConfigurations', array($objectName => $mockObjectConfiguration));

		$objectManager->getObject($objectName);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObjectPassesAdditionalArgumentsToTheObjectBuilderWhenItCreatesANewSessionObject() {
		$objectName = 'mySessionObject';
		$object = $this->getMock('mySessionObject');

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('session'));

		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->any())->method('createObject')->with($objectName, $mockObjectConfiguration, array('overridingArguments'))->will($this->returnValue($object));

		$mockSessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array(), array(), '', FALSE);
		$mockSessionRegistry->expects($this->any())->method('objectExists')->with($objectName)->will($this->returnValue(FALSE));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('isObjectRegistered', 'getOverridingArguments', 'registerShutdownObject'), array(), '', FALSE);
		$objectManager->expects($this->any())->method('getOverridingArguments')->with(array('additionalArguement1', 'additionalArguement2', 'additionalArguement3'))->will($this->returnValue(array('overridingArguments')));
		$objectManager->expects($this->any())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$objectManager->injectObjectBuilder($mockObjectBuilder);
		$objectManager->injectSessionObjectsRegistry($mockSessionRegistry);
		$objectManager->_set('objectConfigurations', array($objectName => $mockObjectConfiguration));

		$objectManager->getObject($objectName, 'additionalArguement1', 'additionalArguement2', 'additionalArguement3');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObjectReturnsSessionObjectsFromTheSessionRegistryIfAnInstanceAlreadyExists() {
		$objectName = 'mySessionObject';
		$object = $this->getMock('mySessionObject');

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('session'));

		$mockSessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array(), array(), '', FALSE);
		$mockSessionRegistry->expects($this->any())->method('objectExists')->with($objectName)->will($this->returnValue(TRUE));
		$mockSessionRegistry->expects($this->once())->method('getObject')->with($objectName)->will($this->returnValue($object));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('isObjectRegistered', 'getOverridingArguments', 'registerShutdownObject'), array(), '', FALSE);
		$objectManager->expects($this->any())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$objectManager->injectSessionObjectsRegistry($mockSessionRegistry);
		$objectManager->_set('objectConfigurations', array($objectName => $mockObjectConfiguration));

		$this->assertEquals($object, $objectManager->getObject($objectName), 'The object was not returned as expected from the session registry.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception
	 */
	public function getObjectThrowsAnExceptionIfTheSessionRegistryIsNotInPlace() {
		$objectName = 'mySessionObject';
		$object = $this->getMock('mySessionObject');

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->any())->method('getScope')->will($this->returnValue('session'));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('isObjectRegistered', 'getOverridingArguments', 'registerShutdownObject'), array(), '', FALSE);
		$objectManager->expects($this->any())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$objectManager->_set('objectConfigurations', array($objectName => $mockObjectConfiguration));

		$objectManager->getObject($objectName);
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$this->assertEquals($objectManager->isObjectRegistered($mockClassName), FALSE, 'isObjectRegistered() did not return FALSE although object is not yet registered.');
		$objectManager->registerObject($mockClassName);
		$this->assertTrue($objectManager->isObjectRegistered($mockClassName), 'isObjectRegistered() did not return TRUE although object has been registered.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registerObjectRegistersTheClassNameOfTheGivenObjectNameForFurtherUsage() {
		$mockObject = $this->getMock('stdclass');
		$mockClassName = get_class($mockObject);

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectObjectFactory($this->getMock('F3\FLOW3\Object\FactoryInterface'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject('someObjectName', $mockClassName);

		$registeredClasses = $objectManager->_get('registeredClasses');
		$this->assertTrue($registeredClasses[$mockClassName] === 'someObjectName');
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObject($className1);
		$objectManager->registerObject($className2);

		$objectConfiguration1 = $objectManager->getObjectConfiguration($className1);
		$objectConfiguration2 = $objectManager->getObjectConfiguration($className2);

		$this->assertEquals(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON, $objectConfiguration1->getScope());
		$this->assertEquals(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE, $objectConfiguration2->getScope());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectPutsTheObjectIntoTheRegistryIfOneWasGiven() {
		$mockObject = $this->getMock('stdclass');
		$mockClassName = get_class($mockObject);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');

		$mockRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObjectType($interfaceName);
		$objectConfiguration = $objectManager->getObjectConfiguration($interfaceName);
		$this->assertEquals(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE, $objectConfiguration->getScope());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registerObjectTypeRegistersTheClassNameIfTheReflectionServiceReturnedOne() {
		$interfaceName = uniqid('SomeInterface');
		eval('interface ' . $interfaceName . ' {}');

		$className = uniqid('SomeClass');
		eval('class ' . $className . ' implements ' . $interfaceName . ' {}');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService
			->expects($this->once())
			->method('getDefaultImplementationClassNameForInterface')
			->with($interfaceName)
			->will($this->returnValue($className));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($mockReflectionService);

		$objectManager->registerObjectType($interfaceName);

		$registeredClasses = $objectManager->_get('registeredClasses');
		$this->assertEquals($interfaceName, $registeredClasses[$className]);
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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

		$mockSingletonObjectsRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function unregisterObjectRemovesTheClassFromTheRegisteredClassesArray() {
		$mockSingletonRegistry = $this->getMock('F3\FLOW3\Object\TransientRegistry');
		$mockSingletonRegistry->expects($this->once())->method('objectExists')->will($this->returnValue(FALSE));

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectConfiguration->expects($this->once())->method('getClassName')->will($this->returnValue('someClassName'));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('isObjectRegistered'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($mockSingletonRegistry);
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->expects($this->once())->method('isObjectRegistered')->with('someObjectName')->will($this->returnValue(TRUE));
		$objectManager->_set('registeredObjects', array('someObjectName' => 'someClassName'));
		$objectManager->_set('registeredClasses', array('someClassName' => 'someObjectName'));
		$objectManager->_set('objectConfigurations', array('someObjectName' => $mockObjectConfiguration));

		$objectManager->unregisterObject('someObjectName');

		$registeredClasses = $objectManager->_get('registeredClasses');
		$this->assertFalse(isset($registeredClasses['someClassName']));
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->registerObject($className);
		$this->assertSame($className, $objectManager->getCaseSensitiveObjectName(strtolower($className)));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObjectNameByClassNameReturnsTheCorrectObjectNameForAGivenClassName() {
		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->_set('registeredClasses', array('someClassName' => 'someObjectName'));

		$this->assertEquals('someObjectName', $objectManager->getObjectNameByClassName('someClassName'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\UnknownClass
	 */
	public function getObjectNameByClassNameThrowsAnExceptionIfTheGivenClassIsNotRegistered() {
		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->getObjectNameByClassName('someClassName');
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

		$configuration1 = new \F3\FLOW3\Object\Configuration\Configuration('Configuration1');
		$configuration2 = new \F3\FLOW3\Object\Configuration\Configuration('Configuration2');
		$newConfiguration2 = new \F3\FLOW3\Object\Configuration\Configuration('Configuration2');
		$newConfiguration3 = new \F3\FLOW3\Object\Configuration\Configuration('Configuration3');

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
		$configuration = new \F3\FLOW3\Object\Configuration\Configuration('Foo');
		$objectManager->setObjectConfiguration($configuration);
		$this->assertTrue($objectManager->isObjectRegistered('Foo'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setObjectConfigurationUpdatesTheRegisteredClassesArrayCorrectlyIfTheClassNameChangedInTheNewObjectConfiguration() {
		$oldObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$oldObjectConfiguration->expects($this->any())->method('getClassName')->will($this->returnValue('someOldClassName'));
		$oldObjectConfiguration->expects($this->any())->method('getObjectName')->will($this->returnValue('someObjectName'));

		$newObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$newObjectConfiguration->expects($this->any())->method('getClassName')->will($this->returnValue('someNewClassName'));
		$newObjectConfiguration->expects($this->any())->method('getObjectName')->will($this->returnValue('someObjectName'));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->_set('objectConfigurations', array('someObjectName' => $oldObjectConfiguration));
		$objectManager->_set('registeredClasses', array('someOldClassName' => 'someObjectName'));

		$objectManager->setObjectConfiguration($newObjectConfiguration);

		$registeredClasses = $objectManager->_get('registeredClasses');
		$this->assertEquals('someObjectName', $registeredClasses['someNewClassName']);
		$this->assertFalse(isset($registeredClasses['someOldClassName']));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setObjectConfigurationAddsANewObjectCorrectlyToTheRegisteredClassesArray() {
		$newObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$newObjectConfiguration->expects($this->any())->method('getClassName')->will($this->returnValue('someNewClassName'));
		$newObjectConfiguration->expects($this->any())->method('getObjectName')->will($this->returnValue('someObjectName'));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->injectObjectBuilder($this->getMock('F3\FLOW3\Object\Builder'));
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$objectManager->setObjectConfiguration($newObjectConfiguration);

		$registeredClasses = $objectManager->_get('registeredClasses');
		$this->assertEquals('someObjectName', $registeredClasses['someNewClassName']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurationStoresACloneOfTheOriginalConfigurationObject() {
		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'));

		$originalConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('Foo');
		$objectManager->setObjectConfiguration($originalConfiguration);

		$internalObjectConfigurations = $objectManager->_get('objectConfigurations');
		$this->assertNotSame($originalConfiguration, $internalObjectConfigurations['Foo']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setObjectConfigurationsDelegatesToSetObjectConfiguration() {
		$originalConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('Foo');

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('setObjectConfiguration'));
		$objectManager->expects($this->once())->method('setObjectConfiguration')->with($originalConfiguration);

		$objectManager->setObjectConfigurations(array($originalConfiguration));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectConfigurationReturnsACloneOfTheOriginalConfigurationObject() {
		$originalConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('Foo');

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'));
		$objectManager->_set('registeredObjects', array('Foo' => TRUE));
		$objectManager->_set('objectConfigurations', array('Foo' => $originalConfiguration));

		$this->assertNotSame($originalConfiguration, $objectManager->getObjectConfiguration('Foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectConfigurationsReturnsClonesOfTheOriginalConfigurationObjects() {
		$originalConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('Foo');

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'));
		$objectManager->_set('registeredObjects', array('Foo' => TRUE));
		$objectManager->_set('objectConfigurations', array('Foo' => $originalConfiguration));

		$fetchedConfigurations = $objectManager->getObjectConfigurations();
		$this->assertNotSame($originalConfiguration, $fetchedConfigurations['Foo']);
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
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
	public function registerAndConfigureAllPackageObjectsRegistersClassesAndInterfacesOfTheGivenPackages() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isAbstract')->will($this->returnValue(FALSE));

		$className1 = 'FooClass' . uniqid();
		$className2 = 'FooClass' . uniqid();
		$className3 = 'BarClass' . uniqid();
		$className4 = 'BlacklistedClass' . uniqid();
		eval('class ' . $className1 . ' {}');
		eval('class ' . $className2 . ' {}');
		eval('class ' . $className3 . ' {}');

		$objectConfigurations = array(
			'Foo' => array($className1 => array('scope' => 'prototype')),
			'Bar' => array($className3 => array('className' => 'Baz'))
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, 'Foo')
			->will($this->returnValue($objectConfigurations['Foo']));
		$mockConfigurationManager->expects($this->at(1))->method('getConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, 'Bar')
			->will($this->returnValue($objectConfigurations['Bar']));

		$packages = array();
		$packages['Foo'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Foo']->expects($this->once())->method('getClassFiles')
			->will($this->returnValue(array($className1 => 'Class1.php', $className2 => 'Class2.php')));

		$packages['Bar'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Bar']->expects($this->once())->method('getClassFiles')
			->will($this->returnValue(array($className3 => 'Class3.php', $className4 => 'Class4.php')));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->_set('reflectionService', $mockReflectionService);
		$objectManager->_set('objectRegistrationClassBlacklist', array($className4));
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$objectManager->_call('registerAndConfigureAllPackageObjects', $packages);

		$actualObjectConfigurations =  $objectManager->getObjectConfigurations();

		$this->assertSame(array($className1, $className2, $className3), array_keys($actualObjectConfigurations));
		$this->assertSame($className1, $actualObjectConfigurations[$className1]->getClassName());
		$this->assertSame(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE, $actualObjectConfigurations[$className1]->getScope());
		$this->assertSame('Baz', $actualObjectConfigurations[$className3]->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerAndConfigureAllPackageObjectsAlsoRegistersInterfacesAsObjectTypes() {
		$className1 = 'FooClass' . uniqid();
		$className2 = 'Foo' . uniqid() . 'Interface';
		eval('class ' . $className1 . ' {}');
		eval('interface ' . $className2 . ' {}');

		$objectConfigurations = array(
			'Foo' => array(),
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isAbstract')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->once())->method('getDefaultImplementationClassNameForInterface')->with($className2)->will($this->returnValue($className1));

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, 'Foo')
			->will($this->returnValue($objectConfigurations['Foo']));

		$packages = array();
		$packages['Foo'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Foo']->expects($this->once())->method('getClassFiles')
			->will($this->returnValue(array($className1 => 'Class1.php', $className2 => 'Interface2.php')));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->_set('reflectionService', $mockReflectionService);
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$objectManager->_call('registerAndConfigureAllPackageObjects', $packages);

		$actualObjectConfigurations =  $objectManager->getObjectConfigurations();

		$this->assertSame(array($className1, $className2), array_keys($actualObjectConfigurations));
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectConfiguration
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerAndConfigureAllPackageObjectsThrowsExceptionOnTryingToConfigureNonRegisteredObjects() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isAbstract')->will($this->returnValue(FALSE));

		$className1 = 'FooClass' . uniqid();
		eval('class ' . $className1 . ' {}');

		$objectConfigurations = array(
			'Foo' => array('Nemo' => array('scope' => 'prototype'))
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, 'Foo')
			->will($this->returnValue($objectConfigurations['Foo']));

		$packages = array();
		$packages['Foo'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Foo']->expects($this->once())->method('getClassFiles')->will($this->returnValue(array($className1 => 'Class1.php')));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\Manager'), array('dummy'), array(), '', FALSE);
		$objectManager->_set('reflectionService', $mockReflectionService);
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$objectManager->_call('registerAndConfigureAllPackageObjects', $packages);
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
		$objectManager->injectSingletonObjectsRegistry($this->getMock('F3\FLOW3\Object\TransientRegistry'));
		$objectManager->injectReflectionService($this->getMock('F3\FLOW3\Reflection\Service'));

		$shutdownObjectsReflection = new \ReflectionProperty($objectManager, 'shutdownObjects');
		$shutdownObjectsReflection->setAccessible(TRUE);
		$shutdownObjectsReflection->setValue($objectManager, $shutdownObjects);

		$objectManager->shutdown();
	}
}
?>