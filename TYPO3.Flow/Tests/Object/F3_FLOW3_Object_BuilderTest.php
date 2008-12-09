<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

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

require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_DummyClass.php');

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Object::BuilderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Object Object Builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Object::BuilderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class BuilderTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Object::Builder
	 */
	protected $objectBuilder;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->objectBuilder = new F3::FLOW3::Object::Builder();
		$this->objectBuilder->injectObjectManager($this->objectManager);
		$this->objectBuilder->injectObjectFactory($this->objectManager->getObjectFactory());
		$this->objectBuilder->injectReflectionService($this->objectManager->getObject('F3::FLOW3::Reflection::Service'));
	}

	/**
	 * Checks if createObject does a simple setter injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSimpleExplicitSetterInjection() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
		$this->assertTrue($object->getFirstDependency() instanceof F3::TestPackage::InjectedClass, 'The class F3::TestPackage::Injected class (first dependency) has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a setter injection with straight values correctly (in this case a string)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionWithStraightValues() {
		$time = microtime();
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$someConfigurationProperty = new F3::FLOW3::Object::ConfigurationProperty('someProperty', $time, F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$objectConfiguration->setProperty($someConfigurationProperty);

		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
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
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$someConfigurationProperty = new F3::FLOW3::Object::ConfigurationProperty('someProperty', $someArray, F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$objectConfiguration->setProperty($someConfigurationProperty);

		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
		$this->assertEquals($someArray, $object->getSomeProperty(), 'The array has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionViaInjectMethod() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
		$this->assertTrue($object->getSecondDependency() instanceof F3::TestPackage::InjectedClass, 'The class F3::TestPackage::Injected class (second dependency) has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectMethodIsPreferredOverSetMethod() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
		$this->assertEquals('inject', $object->injectOrSetMethod, 'Setter inject was done via the set* method but inject* should have been preferred!');
	}

	/**
	 * Checks if createObject does a simple constructor injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSimpleConstructorInjection() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());

		$injectionSucceeded = (
			$object->argument1 instanceof F3::TestPackage::InjectedClass &&
			$object->argument2 === 42 &&
			$object->argument3 === 'Foo Bar Skårhøj'
		);

		$this->assertTrue($injectionSucceeded, 'The class Injected class has not been (correctly) constructor-injected although it should have been.');
	}

	/**
	 * Checks if createObject does a constructor injection with a third dependency correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoConstructorInjectionWithThirdDependency() {
			// load and modify the configuration a bit:
			// ClassWithOptionalConstructorArguments depends on InjectedClassWithDependencies which depends on InjectedClass
		$objectConfigurations = $this->objectManager->getObjectConfigurations();
		$objectConfigurations['F3::TestPackage::ClassWithOptionalConstructorArguments']->setConstructorArgument(new F3::FLOW3::Object::ConfigurationArgument(1, 'F3::TestPackage::InjectedClassWithDependencies', F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		$this->objectManager->setObjectConfigurations($objectConfigurations);
		$objectConfiguration = $objectConfigurations['F3::TestPackage::ClassWithOptionalConstructorArguments'];

		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());

		$this->assertTrue($object->argument1->injectedDependency instanceof F3::TestPackage::InjectedClass, 'Constructor injection with multiple dependencies failed.');
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
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArgument = new F3::FLOW3::Object::ConfigurationArgument(1, $someArray, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		$objectConfiguration->setConstructorArgument($configurationArgument);

		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());
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
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Object::ConfigurationArgument(2, $secondValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new F3::FLOW3::Object::ConfigurationArgument(3, $thirdValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);
		$objectConfiguration->setConstructorArguments($configurationArguments);

		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());
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
		$thirdValue = new ::ArrayObject(array('foo' => 'bar'));
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Object::ConfigurationArgument(1, $firstValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new F3::FLOW3::Object::ConfigurationArgument(3, $thirdValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);
		$objectConfiguration->setConstructorArguments($configurationArguments);

		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());
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

		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Object::ConfigurationArgument(1, $firstValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new F3::FLOW3::Object::ConfigurationArgument(2, $secondValue, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		);
		$objectConfiguration->setConstructorArguments($configurationArguments);

		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertEquals($firstValue, $object->argument1, 'The first value (string with quotes) has not been constructor-injected although it should have been.');
		$this->assertEquals($secondValue, $object->argument2, 'The second value (string with double quotes and backslashes) has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if the object manager itself can be injected by constructor injection
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorInjectionOfObjectManagerWorks() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Object::ConfigurationArgument(1, 'F3::FLOW3::Object::ManagerInterface', F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_REFERENCE),
		);
		$objectConfiguration->setConstructorArguments($configurationArguments);

		$object = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertType('F3::FLOW3::Object::ManagerInterface', $object->argument1, 'The object manager has not been constructor-injected although it should have been.');

		$secondObject = $this->objectBuilder->createObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $objectConfiguration, array());
		$this->assertSame($object->argument1, $secondObject->argument1, 'The constructor-injected instance of the object manager was not a singleton!');
	}

	/**
	 * Checks if the object manager itself can be injected by setter injection
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setterInjectionOfObjectManagerWorks() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$someConfigurationProperty = new F3::FLOW3::Object::ConfigurationProperty('someProperty', 'F3::FLOW3::Object::ManagerInterface', F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE);
		$objectConfiguration->setProperty($someConfigurationProperty);
		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
		$this->assertType('F3::FLOW3::Object::ManagerInterface', $object->getSomeProperty(), 'The object manager has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if the object builder calls the lifecycle initialization method after injecting properties
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCallsLifecycleInitializationMethod() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$object = $this->objectBuilder->createObject('F3::TestPackage::BasicClass', $objectConfiguration, array());
		$this->assertTrue($object->hasBeenInitialized(), 'Obviously the lifecycle initialization method of F3::TestPackage::BasicClass has not been called after setter injection!');
	}

	/**
	 * Checks if autowiring of constructor arguments for dependency injection basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForConstructorInjection() {
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::InjectedClassWithDependencies');
		$object = $this->objectManager->getObject('F3::TestPackage::ClassWithSomeImplementationInjected');
		$this->assertType('F3::TestPackage::SomeImplementation', $object->argument1, 'Autowiring didn\'t work out for F3::TestPackage::ClassWithSomeImplementationInjected');
	}

	/**
	 * Checks if autowiring doesn't override constructor arguments which have already been defined in the object configuration
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringForConstructorInjectionRespectsAlreadyDefinedArguments() {
		$object = $this->objectManager->getObject('F3::TestPackage::ClassWithSomeImplementationInjected');
		$this->assertTrue($object->argument2 instanceof F3::TestPackage::InjectedClassWithDependencies, 'Autowiring didn\'t respect that the second constructor argument was already set in the Objects.ini!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForSetterInjectionViaInjectMethod() {
		$object = $this->objectManager->getObject('F3::TestPackage::ClassWithSomeImplementationInjected');
		$this->assertTrue($object->optionalSetterArgument instanceof F3::TestPackage::SomeInterface, 'Autowiring didn\'t work for the optional setter injection via the inject*() method.');
	}

	/**
	 * @test
	 * @expectedException F3::FLOW3::Object::Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringThrowsExceptionForUnmatchedDependenciesOfRequiredSetterInjectedDependencies() {
		$this->objectManager->getObject('F3::TestPackage::ClassWithUnmatchedRequiredSetterDependency');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectReturnsAnObjectOfTheSpecifiedType() {
		$mockObjectManager = $this->getMock('F3::FLOW3::Object::Manager', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3::FLOW3::Object::Factory', array(), array(), '', FALSE);
		$objectBuilder = new F3::FLOW3::Object::Builder();
		$objectBuilder->injectObjectManager($mockObjectManager);
		$objectBuilder->injectObjectFactory($mockObjectFactory);
		$objectBuilder->injectReflectionService($this->objectManager->getObject('F3::FLOW3::Reflection::Service'));

		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');
		$object = $objectBuilder->reconstituteObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $objectConfiguration, array());
		$this->assertType('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $object);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectRejectsObjectTypesWhichAreNotPersistable() {
		$mockObjectManager = $this->getMock('F3::FLOW3::Object::Manager', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3::FLOW3::Object::Factory', array(), array(), '', FALSE);
		$objectBuilder = new F3::FLOW3::Object::Builder(); 		$objectBuilder->injectObjectManager($mockObjectManager); 		$objectBuilder->injectObjectFactory($mockObjectFactory); 		$objectBuilder->injectReflectionService($this->objectManager->getObject('F3::FLOW3::Reflection::Service'));

		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::NonPersistableClass');

		try {
			$objectBuilder->reconstituteObject('F3::TestPackage::NonPersistableClass', $objectConfiguration, array());
			$this->fail('No exception was thrown.');
		} catch (F3::FLOW3::Object::Exception::CannotReconstituteObject $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectTakesPreventsThatTheConstructorOfTheTargetObjectIsCalled() {
		$mockObjectManager = $this->getMock('F3::FLOW3::Object::Manager', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3::FLOW3::Object::Factory', array(), array(), '', FALSE);
		$objectBuilder = new F3::FLOW3::Object::Builder(); 		$objectBuilder->injectObjectManager($mockObjectManager); 		$objectBuilder->injectObjectFactory($mockObjectFactory); 		$objectBuilder->injectReflectionService($this->objectManager->getObject('F3::FLOW3::Reflection::Service'));
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');

		$object = $objectBuilder->reconstituteObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $objectConfiguration, array());

		$this->assertFalse($object->constructorHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectCallsTheTargetObjectsWakeupMethod() {
		$mockObjectManager = $this->getMock('F3::FLOW3::Object::Manager', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3::FLOW3::Object::Factory', array(), array(), '', FALSE);
		$objectBuilder = new F3::FLOW3::Object::Builder(); 		$objectBuilder->injectObjectManager($mockObjectManager); 		$objectBuilder->injectObjectFactory($mockObjectFactory); 		$objectBuilder->injectReflectionService($this->objectManager->getObject('F3::FLOW3::Reflection::Service'));
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');

		$object = $objectBuilder->reconstituteObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $objectConfiguration, array());

		$this->assertTrue($object->wakeupHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObjectCallsTheTargetObjectsWakeupMethodOnlyAfterAllPropertiesHaveBeenRestored() {
		$mockObjectManager = $this->getMock('F3::FLOW3::Object::Manager', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3::FLOW3::Object::Factory', array(), array(), '', FALSE);
		$objectBuilder = new F3::FLOW3::Object::Builder(); 		$objectBuilder->injectObjectManager($mockObjectManager); 		$objectBuilder->injectObjectFactory($mockObjectFactory); 		$objectBuilder->injectReflectionService($this->objectManager->getObject('F3::FLOW3::Reflection::Service'));
		$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');

		$properties = array(
			'wakeupHasBeenCalled' => FALSE
		);

		$object = $objectBuilder->reconstituteObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $objectConfiguration, $properties);

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