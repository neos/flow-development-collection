<?php
declare(encoding = 'utf-8');

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

require_once(TYPO3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/T3_FLOW3_Fixture_DummyClass.php');

/**
 * Testcase for the Component Object Builder
 *
 * @package		FLOW3
 * @version 	$Id:T3_FLOW3_Component_ObjectBuilderTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Component_ObjectBuilderTest extends T3_Testing_BaseTestCase {

	/**
	 * @var T3_FLOW3_Component_ObjectBuilder
	 */
	protected $componentObjectBuilder;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->componentObjectBuilder = new T3_FLOW3_Component_ObjectBuilder($this->componentManager);
	}

	/**
	 * Checks if createComponentObject does a simple setter injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSimpleSetterInjection() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_BasicClass', $componentConfiguration, array());
		$this->assertTrue($componentObject->getInjectedDependency() instanceof T3_TestPackage_InjectedClass, 'The class T3_TestPackage_Injected class has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a setter injection with straight values correctly (in this case a string)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSetterInjectionWithStraightValues() {
		$time = microtime();
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$someConfigurationProperty = new T3_FLOW3_Component_ConfigurationProperty('someProperty', $time, T3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$componentConfiguration->setProperty($someConfigurationProperty);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_BasicClass', $componentConfiguration, array());
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
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$someConfigurationProperty = new T3_FLOW3_Component_ConfigurationProperty('someProperty', $someArray, T3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
		$componentConfiguration->setProperty($someConfigurationProperty);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_BasicClass', $componentConfiguration, array());
		$this->assertEquals($someArray, $componentObject->getSomeProperty(), 'The array has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if setting the value of a property which is only reachable through a setProperty() method works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSetterInjectionViaGenericSetter() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_BasicClass', $componentConfiguration, array());
		$this->assertEquals('', $componentObject->getPropertyWithoutSetterMethod(), 'The propertyWithoutSetterMethod has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject does a simple constructor injection correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCanDoSimpleConstructorInjection() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());

		$injectionSucceeded = (
			$componentObject->argument1 instanceof T3_TestPackage_InjectedClass &&
			$componentObject->argument2 === 42 &&
			$componentObject->argument3 === 'Foo Bar Skårhøj'
		);

		$this->assertTrue($injectionSucceeded, 'The class T3_TestPackage_Injected class has not been (correctly) constructor-injected although it should have been.');
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
		$componentConfigurations['T3_TestPackage_ClassWithOptionalConstructorArguments']->setConstructorArgument(new T3_FLOW3_Component_ConfigurationArgument(1, 'T3_TestPackage_InjectedClassWithDependencies', T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		$this->componentManager->setComponentConfigurations($componentConfigurations);
		$componentConfiguration = $componentConfigurations['T3_TestPackage_ClassWithOptionalConstructorArguments'];

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());

		$this->assertTrue($componentObject->argument1->injectedDependency instanceof T3_TestPackage_InjectedClass, 'Constructor injection with multiple dependencies failed.');
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
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$configurationArgument = new T3_FLOW3_Component_ConfigurationArgument(1, $someArray, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		$componentConfiguration->setConstructorArgument($configurationArgument);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
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
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new T3_FLOW3_Component_ConfigurationArgument(2, $secondValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new T3_FLOW3_Component_ConfigurationArgument(3, $thirdValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
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
		$thirdValue = new ArrayObject(array('foo' => 'bar'));
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new T3_FLOW3_Component_ConfigurationArgument(1, $firstValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new T3_FLOW3_Component_ConfigurationArgument(3, $thirdValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE)
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
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

		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new T3_FLOW3_Component_ConfigurationArgument(1, $firstValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			new T3_FLOW3_Component_ConfigurationArgument(2, $secondValue, T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
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
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$configurationArguments = array(
			new T3_FLOW3_Component_ConfigurationArgument(1, 'T3_FLOW3_Component_ManagerInterface', T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE),
		);
		$componentConfiguration->setConstructorArguments($configurationArguments);

		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertType('T3_FLOW3_Component_ManagerInterface', $componentObject->argument1, 'The component manager has not been constructor-injected although it should have been.');

		$secondComponentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		$this->assertSame($componentObject->argument1, $secondComponentObject->argument1, 'The constructor-injected instance of the component manager was not a singleton!');
	}

	/**
	 * Checks if the component manager itself can be injected by setter injection
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setterInjectionOfComponentManagerWorks() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$someConfigurationProperty = new T3_FLOW3_Component_ConfigurationProperty('someProperty', 'T3_FLOW3_Component_ManagerInterface', T3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_REFERENCE);
		$componentConfiguration->setProperty($someConfigurationProperty);
		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_BasicClass', $componentConfiguration, array());
		$this->assertType('T3_FLOW3_Component_ManagerInterface', $componentObject->getSomeProperty(), 'The component manager has not been setter-injected although it should have been.');
	}

	/**
	 * Checks if createComponentObject handles circular dependencies correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectHandlesCircularDependenciesCorrectly() {
			// load and modify the configuration a bit:
			// ClassWithOptionalConstructorArguments depends on InjectedClassWithDependencies which depends on ClassWithOptionalConstructorArguments
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_InjectedClassWithDependencies');
		$componentConfiguration->setConstructorArgument(new T3_FLOW3_Component_ConfigurationArgument(1, 'T3_TestPackage_ClassWithOptionalConstructorArguments', T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		$this->componentManager->setComponentConfiguration($componentConfiguration);

		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_ClassWithOptionalConstructorArguments');
		$componentConfiguration->setConstructorArgument(new T3_FLOW3_Component_ConfigurationArgument(1, 'T3_TestPackage_InjectedClassWithDependencies', T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		$this->componentManager->setComponentConfiguration($componentConfiguration);

		try {
			$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_ClassWithOptionalConstructorArguments', $componentConfiguration, array());
		} catch (Exception $exception) {
			$this->assertEquals(1168505928, $exception->getCode(), 'createComponentObject() throwed an exception for circular dependencies but returned the wrong error code.');
			return;
		}
		$this->fail('createComponentObject() did not throw an exception although circular dependencies existed.');
	}

	/**
	 * Checks if the object builder calls the lifecycle initialization method after injecting properties
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createComponentObjectCallsLifecycleInitializationMethod() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$componentObject = $this->componentObjectBuilder->createComponentObject('T3_TestPackage_BasicClass', $componentConfiguration, array());
		$this->assertTrue($componentObject->hasBeenInitialized(), 'Obviously the lifecycle initialization method of T3_TestPackage_BasicClass has not been called after setter injection!');
	}

	/**
	 * Checks if autowiring of constructor arguments for dependency injection basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringBasicallyWorks() {
		$componentConfiguration = $this->componentManager->getComponentConfiguration('T3_TestPackage_InjectedClassWithDependencies');
		$component = $this->componentManager->getComponent('T3_TestPackage_ClassWithSomeImplementationInjected');
		$this->assertType('T3_TestPackage_SomeImplementation', $component->argument1, 'Autowiring didn\'t work out for T3_TestPackage_ClassWithSomeImplementationInjected');
	}

	/**
	 * Checks if autowiring doesn't override constructor arguments which have already been defined in the component configuration
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function autoWiringRespectsAlreadyDefinedArguments() {
		$component = $this->componentManager->getComponent('T3_TestPackage_ClassWithSomeImplementationInjected');
		$this->assertTrue($component->argument2 instanceof T3_TestPackage_InjectedClassWithDependencies, 'Autowiring didn\'t respect that the second constructor argument was already set in the Components.ini!');
	}
}
?>