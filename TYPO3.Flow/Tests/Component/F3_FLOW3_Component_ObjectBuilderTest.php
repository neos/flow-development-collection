<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Component;

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
 * @version $Id:F3::FLOW3::Component::ObjectBuilderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Component Object Builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ObjectBuilderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ObjectBuilderTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Component::ObjectBuilder
	 */
	protected $componentObjectBuilder;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->componentObjectBuilder = new F3::FLOW3::Component::ObjectBuilder($this->componentFactory, $this->componentFactory->getComponent('F3::FLOW3::Reflection::Service'));
	}

	/**
	 * Checks if createComponentObject does a simple setter injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSimpleExplicitSetterInjection() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertTrue($componentObject->getFirstDependency() instanceof F3::TestPackage::InjectedClass, 'The class F3::TestPackage::Injected class (first dependency) has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a setter injection with straight values correctly (in this case a string)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSetterInjectionWithStraightValues() {
		$time = microtime();
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$someConfigurationProperty = new F3::FLOW3::Component::ConfigurationProperty('someProperty', $time, F3::FLOW3::Component::ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$componentConfiguration->setProperty($someConfigurationProperty);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertEquals($time, $componentObject->getSomeProperty(), 'The straight value has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a setter injection with arrays correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSetterInjectionWithArrays() {
		$someArray = array(
			'foo' => 'bar',
			199 => 837,
			'doo' => TRUE
		);
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$someConfigurationProperty = new F3::FLOW3::Component::ConfigurationProperty('someProperty', $someArray, F3::FLOW3::Component::ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$componentConfiguration->setProperty($someConfigurationProperty);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertEquals($someArray, $componentObject->getSomeProperty(), 'The array has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObjectCanDoSetterInjectionViaInjectMethod() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertTrue($componentObject->getSecondDependency() instanceof F3::TestPackage::InjectedClass, 'The class F3::TestPackage::Injected class (second dependency) has not been setter-injected although it should have been.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectMethodIsPreferredOverSetMethod() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertEquals('inject', $componentObject->injectOrSetMethod, 'Setter inject was done via the set* method but inject* should have been preferred!');
	}

	/**
	 * Checks if createComponentObject does a simple constructor injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSimpleConstructorInjection() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());

		$injectionSucceeded = (
			$componentObject->argument1 instanceof F3::TestPackage::InjectedClass &&
			$componentObject->argument2 === 42 &&
			$componentObject->argument3 === 'Foo Bar Skårhøj'
		);

		$this->assertTrue($injectionSucceeded, 'The class Injected class has not been (correctly) constructor-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a constructor injection with a third dependency correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoConstructorInjectionWithThirdDependency() {
			// load and modify the configuration a bit:
			// ClassWithOptionalConstructorArguments depends on InjectedClassWithDependencies which depends on InjectedClass
		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		$componentConfigurations['F3::TestPackage::ClassWithOptionalConstructorArguments']->setConstructorArgument(new F3::FLOW3::Component::ConfigurationArgument(1, 'F3::TestPackage::InjectedClassWithDependencies', F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		$this->componentManager->setComponentConfigurations($componentConfigurations);
		$componentConfiguration = $componentConfigurations['F3::TestPackage::ClassWithOptionalConstructorArguments'];

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());

		$this->assertTrue($componentObject->argument1->injectedDependency instanceof F3::TestPackage::InjectedClass, 'Constructor injection with multiple dependencies failed.');
	}

	/**
	 * Checks if createComponentObject does a constructor injection with arrays correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoConstructorInjectionWithArrays() {
		$someArray = array(
			'foo' => 'bar',
			199 => 837,
			'doo' => TRUE
		);
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArgument = new F3::FLOW3::Component::ConfigurationArgument(1, $someArray, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		$componentConfiguration->setConstructorArgument($configurationArgument);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertEquals($someArray, $componentObject->argument1, 'The array has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a constructor injection with numeric values correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoConstructorInjectionWithNumericValues() {
		$secondValue = 99;
		$thirdValue = 3.14159265359;
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Component::ConfigurationArgument(2, $secondValue, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new F3::FLOW3::Component::ConfigurationArgument(3, $thirdValue, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertEquals($secondValue, $componentObject->argument2, 'The second straight numeric value has not been constructor-injected although it should have been.');
		$this->assertEquals($thirdValue, $componentObject->argument3, 'The third straight numeric value has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a constructor injection with boolean values and objects correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoConstructorInjectionWithBooleanValuesAndObjects() {
		$firstValue = TRUE;
		$thirdValue = new ::ArrayObject(array('foo' => 'bar'));
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Component::ConfigurationArgument(1, $firstValue, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new F3::FLOW3::Component::ConfigurationArgument(3, $thirdValue, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertEquals($firstValue, $componentObject->argument1, 'The first value (boolean) has not been constructor-injected although it should have been.');
		$this->assertEquals($thirdValue, $componentObject->argument3, 'The third argument (an object) has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject can handle difficult constructor arguments (with quotes, special chars etc.)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoConstructorInjectionWithDifficultArguments() {
		$firstValue = "Hir hier deser d'Sonn am, fu dem Ierd d'Liewen, ze schéinste Kirmesdag hannendrun déi.";
		$secondValue = 'Oho ha halo\' maksimume, "io fari jeso naŭ plue" om backslash (\\)nea komo triliono postpostmorgaŭ.';

		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Component::ConfigurationArgument(1, $firstValue, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new F3::FLOW3::Component::ConfigurationArgument(2, $secondValue, F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertEquals($firstValue, $componentObject->argument1, 'The first value (string with quotes) has not been constructor-injected although it should have been.');
		$this->assertEquals($secondValue, $componentObject->argument2, 'The second value (string with double quotes and backslashes) has not been constructor-injected although it should have been.');
	}

	/**
	 * Checks if the component manager itself can be injected by constructor injection
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorInjectionOfComponentManagerWorks() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new F3::FLOW3::Component::ConfigurationArgument(1, 'F3::FLOW3::Component::ManagerInterface', F3::FLOW3::Component::ConfigurationArgument::ARGUMENT_TYPES_REFERENCE),
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertType('F3::FLOW3::Component::ManagerInterface', $componentObject->argument1, 'The component manager has not been constructor-injected although it should have been.');

		$secondComponentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertSame($componentObject->argument1, $secondComponentObject->argument1, 'The constructor-injected instance of the component manager was not a singleton!');
	}

	/**
	 * Checks if the component manager itself can be injected by setter injection
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setterInjectionOfComponentManagerWorks() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$someConfigurationProperty = new F3::FLOW3::Component::ConfigurationProperty('someProperty', 'F3::FLOW3::Component::ManagerInterface', F3::FLOW3::Component::ConfigurationProperty::PROPERTY_TYPES_REFERENCE);
		$componentConfiguration->setProperty($someConfigurationProperty);
		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertType('F3::FLOW3::Component::ManagerInterface', $componentObject->getSomeProperty(), 'The component manager has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if the object builder calls the lifecycle initialization method after injecting properties
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCallsLifecycleInitializationMethod() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('F3::TestPackage::BasicClass', $componentConfiguration, array());
		$this->assertTrue($componentObject->hasBeenInitialized(), 'Obviously the lifecycle initialization method of F3::TestPackage::BasicClass has not been called after setter injection!');
	}

	/**
	 * Checks if autowiring of constructor arguments for dependency injection basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForConstructorInjection() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::InjectedClassWithDependencies');
		$component = $this->componentFactory->getComponent('F3::TestPackage::ClassWithSomeImplementationInjected');
		$this->assertType('F3::TestPackage::SomeImplementation', $component->argument1, 'Autowiring didn\'t work out for F3::TestPackage::ClassWithSomeImplementationInjected');
	}

	/**
	 * Checks if autowiring doesn't override constructor arguments which have already been defined in the component configuration
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringForConstructorInjectionRespectsAlreadyDefinedArguments() {
		$component = $this->componentFactory->getComponent('F3::TestPackage::ClassWithSomeImplementationInjected');
		$this->assertTrue($component->argument2 instanceof F3::TestPackage::InjectedClassWithDependencies, 'Autowiring didn\'t respect that the second constructor argument was already set in the Components.ini!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringWorksForSetterInjectionViaInjectMethod() {
		$component = $this->componentFactory->getComponent('F3::TestPackage::ClassWithSomeImplementationInjected');
		$this->assertTrue($component->optionalSetterArgument instanceof F3::TestPackage::SomeInterface, 'Autowiring didn\'t work for the optional setter injection via the inject*() method.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringThrowsExceptionForUnmatchedDependenciesOfRequiredSetterInjectedDependencies() {
		try {
			$this->componentFactory->getComponent('F3::TestPackage::ClassWithUnmatchedRequiredSetterDependency');
			$this->fail('The object builder did not throw an exception.');
		} catch (F3::FLOW3::Component::Exception::CannotBuildObject $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteComponentObjectReturnsAnObjectOfTheSpecifiedType() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::Factory', array(), array(), '', FALSE);
		$componentObjectBuilder = new F3::FLOW3::Component::ObjectBuilder($mockComponentFactory, $this->componentFactory->getComponent('F3::FLOW3::Reflection::Service'));

		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');
		$object = $componentObjectBuilder->reconstituteComponentObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $componentConfiguration, array());
		$this->assertType('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $object);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteComponentObjectRejectsComponentTypesWhichAreNotPersistable() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::Factory', array(), array(), '', FALSE);
		$componentObjectBuilder = new F3::FLOW3::Component::ObjectBuilder($mockComponentFactory, $this->componentFactory->getComponent('F3::FLOW3::Reflection::Service'));

		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::NonPersistableClass');

		try {
			$componentObjectBuilder->reconstituteComponentObject('F3::TestPackage::NonPersistableClass', $componentConfiguration, array());
			$this->fail('No exception was thrown.');
		} catch (F3::FLOW3::Component::Exception::CannotReconstituteObject $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteComponentObjectTakesPreventsThatTheConstructorOfTheTargetObjectIsCalled() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::Factory', array(), array(), '', FALSE);
		$componentObjectBuilder = new F3::FLOW3::Component::ObjectBuilder($mockComponentFactory, $this->componentFactory->getComponent('F3::FLOW3::Reflection::Service'));
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');

		$object = $componentObjectBuilder->reconstituteComponentObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $componentConfiguration, array());

		$this->assertFalse($object->constructorHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteComponentObjectCallsTheTargetObjectsWakeupMethod() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::Factory', array(), array(), '', FALSE);
		$componentObjectBuilder = new F3::FLOW3::Component::ObjectBuilder($mockComponentFactory, $this->componentFactory->getComponent('F3::FLOW3::Reflection::Service'));
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');

		$object = $componentObjectBuilder->reconstituteComponentObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $componentConfiguration, array());

		$this->assertTrue($object->wakeupHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteComponentObjectCallsTheTargetObjectsWakeupMethodOnlyAfterAllPropertiesHaveBeenRestored() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::Factory', array(), array(), '', FALSE);
		$componentObjectBuilder = new F3::FLOW3::Component::ObjectBuilder($mockComponentFactory, $this->componentFactory->getComponent('F3::FLOW3::Reflection::Service'));
		$componentConfiguration = $this->componentManager->getComponentConfiguration('F3::TestPackage::ReconstitutableClassWithSimpleProperties');

		$properties = array(
			'wakeupHasBeenCalled' => FALSE
		);

		$object = $componentObjectBuilder->reconstituteComponentObject('F3::TestPackage::ReconstitutableClassWithSimpleProperties', $componentConfiguration, $properties);

		$this->assertTrue($object->wakeupHasBeenCalled);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteComponentObjectTriesToDependencyInjectPropertiesWhichAreNotPersistable() {
		$this->markTestIncomplete('Not yet implemented');
	}
}
?>