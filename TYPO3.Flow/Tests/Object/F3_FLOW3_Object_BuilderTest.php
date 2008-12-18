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

require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_BasicClass.php');
require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_ClassWithOptionalConstructorArguments.php');
require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_SomeInterface.php');
require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_SomeImplementation.php');
require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_ClassWithSomeImplementationInjected.php');
require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_ReconstitutableClassWithSimpleProperties.php');
require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Object/Fixture/F3_FLOW3_Tests_Object_Fixture_ClassWithUnmatchedRequiredSetterDependency.php');

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\BuilderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Object Object Builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\BuilderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class BuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Object\Factory
	 */
	protected $mockObjectFactory;

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $mockReflectionService;

	/**
	 * @var \F3\FLOW3\Object\Builder
	 */
	protected $objectBuilder;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$this->mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$this->objectBuilder = new \F3\FLOW3\Object\Builder();
		$this->objectBuilder->injectObjectManager($this->mockObjectManager);
		$this->objectBuilder->injectObjectFactory($this->mockObjectFactory);
		$this->objectBuilder->injectReflectionService($this->mockReflectionService);
	}

	/**
	 * Checks if createObject does a simple setter injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSimpleExplicitSetterInjection() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$objectConfiguration->setLifecycleInitializationMethod('initializeAfterPropertiesSet');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
			new \F3\FLOW3\Object\ConfigurationProperty('secondDependency', $injectedClassName, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
			new \F3\FLOW3\Object\ConfigurationProperty('injectOrSetMethod', 'dummy', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());

		$this->assertSame($object->getFirstDependency(), $injectedClass, 'The class ' . $injectedClassName . ' (first dependency) has not been setter-injected although it should have been.' . get_class($object->getFirstDependency()));
	}

	/**
	 * Checks if createObject does a setter injection with straight values correctly (in this case a string)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionWithStraightValues() {
		$time = microtime();
		$someConfigurationProperty = new \F3\FLOW3\Object\ConfigurationProperty('someProperty', $time, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');
		$objectConfiguration->setProperty($someConfigurationProperty);

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());
		$this->assertEquals($time, $object->getSomeProperty(), 'The straight value has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a setter injection with arrays correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionWithArrays() {
		$someArray = array(
			'foo' => 'bar',
			199 => 837,
			'doo' => TRUE
		);
		$someConfigurationProperty = new \F3\FLOW3\Object\ConfigurationProperty('someProperty', $someArray, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');
		$objectConfiguration->setProperty($someConfigurationProperty);

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());
		$this->assertEquals($someArray, $object->getSomeProperty(), 'The array has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionViaInjectMethod() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$objectConfiguration->setLifecycleInitializationMethod('initializeAfterPropertiesSet');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
			new \F3\FLOW3\Object\ConfigurationProperty('secondDependency', $injectedClassName, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
			new \F3\FLOW3\Object\ConfigurationProperty('injectOrSetMethod', 'dummy', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());

		$this->assertSame($object->getSecondDependency(), $injectedClass, 'The class ' . $injectedClassName . ' (second dependency) has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectMethodIsPreferredOverSetMethod() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\ConfigurationProperty('injectOrSetMethod', 'dummy', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());
		$this->assertEquals('inject', $object->injectOrSetMethod, 'Setter inject was done via the set* method but inject* should have been preferred!');
	}

	/**
	 * Checks if createObject does a simple constructor injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSimpleConstructorInjection() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . '{}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->once())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments');
		$objectConfiguration->setConstructorArguments(array(
			new \F3\FLOW3\Object\ConfigurationArgument(1, $injectedClassName, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_REFERENCE),
			new \F3\FLOW3\Object\ConfigurationArgument(2, 42, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\ConfigurationArgument(3, 'Foo Bar Skårhøj', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments', $objectConfiguration, array());

		$injectionSucceeded = (
			$object->argument1 === $injectedClass &&
			$object->argument2 === 42 &&
			$object->argument3 === 'Foo Bar Skårhøj'
		);
		$this->assertTrue($injectionSucceeded, 'The class Injected class has not been (correctly) constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with a third dependency correctly
	 *
	 * @ test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithThirdDependency() {
			// load and modify the configuration a bit:
			// ClassWithOptionalConstructorArguments depends on InjectedClassWithDependencies which depends on InjectedClass
		$objectConfigurations = $this->objectManager->getObjectConfigurations();
		$objectConfigurations['F3\TestPackage\ClassWithOptionalConstructorArguments']->setConstructorArgument(new \F3\FLOW3\Object\ConfigurationArgument(1, 'F3\TestPackage\InjectedClassWithDependencies', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		$this->objectManager->setObjectConfigurations($objectConfigurations);
		$objectConfiguration = $objectConfigurations['F3\TestPackage\ClassWithOptionalConstructorArguments'];

		$object = $this->objectBuilder->createObject('F3\TestPackage\ClassWithOptionalConstructorArguments', $objectConfiguration, array());

		$this->assertTrue($object->argument1->injectedDependency instanceof \F3\TestPackage\InjectedClass, 'Constructor injection with multiple dependencies failed.');
	}

	/**
	 * Checks if createObject does a constructor injection with arrays correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithArrays() {
		$someArray = array(
			'foo' => 'bar',
			199 => 837,
			'doo' => TRUE
		);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments');
		$objectConfiguration->setConstructorArguments(array(
			new \F3\FLOW3\Object\ConfigurationArgument(1, $someArray, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertEquals($someArray, $object->argument1, 'The array has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with numeric values correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithNumericValues() {
		$secondValue = 99;
		$thirdValue = 3.14159265359;
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments');
		$objectConfiguration->setConstructorArguments(array(
			new \F3\FLOW3\Object\ConfigurationArgument(2, $secondValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\ConfigurationArgument(3, $thirdValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertEquals($secondValue, $object->argument2, 'The second straight numeric value has not been constructor-injected although it should have been.');
		$this->assertEquals($thirdValue, $object->argument3, 'The third straight numeric value has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with boolean values and objects correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithBooleanValuesAndObjects() {
		$firstValue = TRUE;
		$thirdValue = new \ArrayObject(array('foo' => 'bar'));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments');
		$objectConfiguration->setConstructorArguments(array(
			new \F3\FLOW3\Object\ConfigurationArgument(1, $firstValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\ConfigurationArgument(3, $thirdValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertEquals($firstValue, $object->argument1, 'The first value (boolean) has not been constructor-injected although it should have been.');
		$this->assertEquals($thirdValue, $object->argument3, 'The third argument (an object) has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject can handle difficult constructor arguments (with quotes, special chars etc.)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithDifficultArguments() {
		$firstValue = "Hir hier deser d'Sonn am, fu dem Ierd d'Liewen, ze schéinste Kirmesdag hannendrun déi.";
		$secondValue = 'Oho ha halo\' maksimume, "io fari jeso naŭ plue" om backslash (\\)nea komo triliono postpostmorgaŭ.';

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments');
		$objectConfiguration->setConstructorArguments(array(
			new \F3\FLOW3\Object\ConfigurationArgument(1, $firstValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new \F3\FLOW3\Object\ConfigurationArgument(2, $secondValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertEquals($firstValue, $object->argument1, 'The first value (string with quotes) has not been constructor-injected although it should have been.');
		$this->assertEquals($secondValue, $object->argument2, 'The second value (string with double quotes and backslashes) has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if the object builder calls the lifecycle initialization method after injecting properties
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCallsLifecycleInitializationMethod() {
		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . ' {}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->any())->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));

		$objectName = 'F3\FLOW3\Tests\Object\Fixture\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$objectConfiguration->setLifecycleInitializationMethod('initializeAfterPropertiesSet');
		$objectConfiguration->setProperties(array(
			new \F3\FLOW3\Object\ConfigurationProperty('firstDependency', $injectedClassName, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
		));

		$object = $this->objectBuilder->createObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());
		$this->assertTrue($object->hasBeenInitialized(), 'Obviously the lifecycle initialization method of \F3\TestPackage\BasicClass has not been called after setter injection!');
	}

	/**
	 * Checks if autowiring of constructor arguments for dependency injection basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForConstructorInjection() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected';
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\BasicClass')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\BasicClass));
		$this->mockReflectionService->expects($this->once())->method('getClassConstructorName')->with($objectName)->will($this->returnValue('__construct'));
		$constructorParameters = array(
			'argument1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
			),
			'argument2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\BasicClass'
			)
		);
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, '__construct')->will($this->returnValue($constructorParameters));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected');

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration, array());
		$this->assertType('F3\FLOW3\Tests\Object\Fixture\SomeImplementation', $object->argument1, 'Autowiring didn\'t work out for ' . $objectName);
	}

	/**
	 * Checks if autowiring doesn't override constructor arguments which have already been defined in the object configuration
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringForConstructorInjectionRespectsAlreadyDefinedArguments() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected';
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));
		$this->mockReflectionService->expects($this->once())->method('getClassConstructorName')->with($objectName)->will($this->returnValue('__construct'));
		$constructorParameters = array(
			'argument1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
			),
			'argument2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\BasicClass'
			)
		);
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, '__construct')->will($this->returnValue($constructorParameters));

		$injectedClassName = uniqid('Injected');
		eval('namespace F3\Virtual; class ' . $injectedClassName . ' extends \F3\FLOW3\Tests\Object\Fixture\BasicClass {}');
		$injectedClassName = 'F3\Virtual\\' .$injectedClassName;
		$injectedClass = new $injectedClassName();
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with($injectedClassName)->will($this->returnValue($injectedClass));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected');
		$objectConfiguration->setConstructorArguments(array(
			new \F3\FLOW3\Object\ConfigurationArgument(2, $injectedClassName, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_REFERENCE)
		));

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration, array());
		$this->assertSame($object->argument2, $injectedClass, 'Autowiring didn\'t respect that the second constructor argument was already set in the object configuration!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForSetterInjectionViaInjectMethod() {
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithSomeImplementationInjected';
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\BasicClass')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\BasicClass));
		$this->mockReflectionService->expects($this->once())->method('getClassConstructorName')->with($objectName)->will($this->returnValue('__construct'));
		$constructorParameters = array(
			'argument1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
			),
			'argument2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\BasicClass'
			)
		);
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, '__construct')->will($this->returnValue($constructorParameters));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);

		$setterParameters = array(array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'F3\FLOW3\Tests\Object\Fixture\SomeInterface'
		));
		$this->mockReflectionService->expects($this->at(2))->method('getMethodParameters')->with($objectName, 'injectOptionalSetterArgument')->will($this->returnValue($setterParameters));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Tests\Object\Fixture\SomeInterface')->will($this->returnValue(new \F3\FLOW3\Tests\Object\Fixture\SomeImplementation));

		$object = $this->objectBuilder->createObject($objectName, $objectConfiguration, array());
		$this->assertType('F3\FLOW3\Tests\Object\Fixture\SomeImplementation', $object->optionalSetterArgument , 'Autowiring didn\'t work for the optional setter injection via the inject*() method.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception
	 */
	public function autoWiringThrowsExceptionForUnmatchedDependenciesOfRequiredSetterInjectedDependencies() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('stdClass')->will($this->throwException(new \F3\FLOW3\Object\Exception()));
		$objectName = 'F3\FLOW3\Tests\Object\Fixture\ClassWithUnmatchedRequiredSetterDependency';
		$setterParameters = array(array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => 'stdClass'
		));
		$this->mockReflectionService->expects($this->at(1))->method('getMethodParameters')->with($objectName, 'injectRequiredSetterArgument')->will($this->returnValue($setterParameters));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);

		$this->objectBuilder->createObject($objectName, $objectConfiguration, array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectReturnsAnObjectOfTheSpecifiedType() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');

		$object = $this->objectBuilder->reconstituteObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration, array());
		$this->assertType('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $object);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\CannotReconstituteObject
	 */
	public function reconstituteObjectRejectsObjectTypesWhichAreNotPersistable() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\BasicClass');

		$this->objectBuilder->reconstituteObject('F3\FLOW3\Tests\Object\Fixture\BasicClass', $objectConfiguration, array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectPreventsThatTheConstructorOfTheTargetObjectIsCalled() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');

		$object = $this->objectBuilder->reconstituteObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration, array());
		$this->assertFalse($object->constructorHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectCallsTheTargetObjectsWakeupMethod() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');

		$object = $this->objectBuilder->reconstituteObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration, array());
		$this->assertTrue($object->wakeupHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectCallsTheTargetObjectsWakeupMethodOnlyAfterAllPropertiesHaveBeenRestored() {
		$objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties');

		$properties = array(
			'wakeupHasBeenCalled' => FALSE
		);

		$object = $this->objectBuilder->reconstituteObject('F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties', $objectConfiguration, $properties);
		$this->assertTrue($object->wakeupHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectTriesToDependencyInjectPropertiesWhichAreNotPersistable() {
		$this->markTestIncomplete('Not yet implemented');
	}
}
?>