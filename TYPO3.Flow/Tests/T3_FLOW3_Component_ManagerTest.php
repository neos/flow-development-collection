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

/**
 * Testcase for the default component manager
 * 
 * @package		FLOW3
 * @version 	$Id:T3_FLOW3_Component_ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Component_ManagerTest extends T3_Testing_BaseTestCase {
	
	/**
	 * Checks if getContext() returns the "default" context if nothing else has been defined.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContextReturnsDefaultContext() {
		$componentManager = new T3_FLOW3_Component_Manager();
		$this->assertEquals('default', $componentManager->getContext(), 'getContext() did not return "default".');
	}
	
	/**
	 * Checks if setting and retrieving the context delivers the expected results
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org> 
	 */
	public function setContextBasicallyWorks() {
		$componentManager = new T3_FLOW3_Component_Manager();
		$componentManager->setContext('halululu');
		$this->assertEquals('halululu', $componentManager->getContext(), 'getContext() did not return the context we set.');
			
	}
	
	/**
	 * Checks if getComponent() returns the expected class type
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsCorrectClassType() {
		$testComponentInstance = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
		$this->assertTrue($testComponentInstance instanceof T3_TestPackage_BasicClass, 'Component instance is no instance of our basic test class!');
	}

	/**
	 * Checks if getComponent() fails on non-existing components
	 * 
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentFailsOnNonExistentComponent() {
		try {
			$testComponentInstance = $this->componentManager->getComponent('T3_TestPackage_ThisClassDoesNotExist');
		} catch (T3_FLOW3_Component_Exception_UnknownComponent $exception) {
			return;
		}
		$this->fail('getComponent() did not throw an exception although it has been asked for a non-existent component.');
	}

	/**
	 * Checks if getComponent() delivers a unique instance of the component with the default configuration
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsUniqueInstanceByDefault() {
		$firstInstance = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
		$secondInstance = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
		$this->assertSame($secondInstance, $firstInstance, 'getComponent() did not return a truly unique instance when asked for a non-configured component.');
	}

	/**
	 * Checks if getComponent() delivers a prototype of a component which is configured as a prototype
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsPrototypeInstanceIfConfigured() {
		$firstInstance = $this->componentManager->getComponent('T3_TestPackage_PrototypeClass');
		$secondInstance = $this->componentManager->getComponent('T3_TestPackage_PrototypeClass');
		$this->assertNotSame($secondInstance, $firstInstance, 'getComponent() did not return a fresh prototype instance when asked for a component configured as prototype.');
	}
	
	/**
	 * Checks if getComponent() delivers the correct class if the class name is different from the component name
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsCorrectClassIfDifferentFromComponentName() {
		$component = $this->componentManager->getComponent('T3_TestPackage_ClassToBeReplaced');
		$this->assertTrue($component instanceof T3_TestPackage_ReplacingClass, 'getComponent() did not return a the replacing class.');
	}
	
	/**
	 * Checks if getComponent() passes arguments to the constructor of a component class
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentPassesArgumentsToComponentClassConstructor() {
		$component = $this->componentManager->getComponent('T3_TestPackage_ClassWithOptionalConstructorArguments', 'test1', 'test2', 'test3');
		$checkSucceeded = (
			$component->argument1 == 'test1' &&
			$component->argument2 == 'test2' &&
			$component->argument3 == 'test3'
		);
		$this->assertTrue($checkSucceeded, 'getComponent() did not instantiate the component with the specified constructor parameters.');
	}
	
	/**
	 * Checks if registerComponent() can register valid and unspectactular classes
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentCanRegisterNormalClasses() {
		$componentManager = new T3_FLOW3_Component_Manager();
		$this->assertEquals($componentManager->isComponentRegistered('T3_TestPackage_BasicClass'), FALSE, 'isComponentRegistered() did not return FALSE although component is not yet registered.');
		$componentManager->registerComponent('T3_TestPackage_BasicClass');
		$this->assertTrue($componentManager->isComponentRegistered('T3_TestPackage_BasicClass'), 'isComponentRegistered() did not return TRUE although component has been registered.');
	}

	/**
	 * Checks if registerComponent() can register classes in sub directories to the
	 * Classes/ directory.
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentCanRegisterClassesInSubDirectories() {
		$componentManager = new T3_FLOW3_Component_Manager();
		$this->assertFalse($componentManager->isComponentRegistered('T3_TestPackage_BasicClass'), 'isComponentRegistered() did not return FALSE although component is not yet registered.');
		$this->assertFalse($componentManager->isComponentRegistered('T3_TestPackage_SubDirectory_ClassInSubDirectory'), 'isComponentRegistered() did not return FALSE although component is not yet registered.');
		$componentManager->registerComponent('T3_TestPackage_SubDirectory_ClassInSubDirectory');
		$this->assertTrue($this->componentManager->isComponentRegistered('T3_TestPackage_SubDirectory_ClassInSubDirectory'), 'isComponentRegistered() did not return TRUE although component has been registered.');
	}
	
	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentRejectsAbstractClasses() {
		$componentManager = new T3_FLOW3_Component_Manager();
		$this->assertFalse($componentManager->isComponentRegistered('T3_TestPackage_AbstractClass'), 'isComponentRegistered() did not return FALSE although the abstract class is not yet registered.');
		try {
			$componentManager->registerComponent('T3_TestPackage_AbstractClass');
			$this->fail('The component manager did not reject the registration of an abstract class.');
		} catch (T3_FLOW3_Component_Exception_InvalidClass $exception) {
			return;
		}
		$this->fail('The component manager did not throw the right kind of exception.');
	}
	
	/**
	 * Checks if unregisterComponent() unregisters components
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function unregisterComponentThrowsExceptionForNonExistentComponent() {
		try {
			$this->componentManager->unregisterComponent('T3_NonExistentPackage_NonExistentClass');
		} catch (T3_FLOW3_Component_Exception_UnknownComponent $exception) {
			return;
		}
		$this->fail('unregisterComponent() did not throw an exception while unregistering a non existent or not registered component.');
	}
	
	/**
	 * Checks if unregisterComponent() unregisters components
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function unregisterComponentReallyUnregistersComponents() {
		$this->assertEquals($this->componentManager->isComponentRegistered('T3_TestPackage_BasicClass'), TRUE, 'T3_TestPackage_BasicClass is not a registered component.');
		$this->componentManager->unregisterComponent('T3_TestPackage_BasicClass');
		$this->assertEquals($this->componentManager->isComponentRegistered('T3_TestPackage_BasicClass'), FALSE, 'isComponentRegistered() did not return FALSE although component should not be registered anymore.');
	}
	
	/**
	 * Checks if setComponentConfigurations() throws an exception if the configuration is no valid configuration object
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function setComponentConfigurationsThrowsExceptionForNonArray() {
		try {
			$this->componentManager->setComponentconfigurations(array('T3_TestPackage_BasicClass' => 'Some string'));
		} catch (Exception $exception) {
			$this->assertEquals(1167826954, $exception->getCode(), 'setComponentConfigurations() throwed an exception but returned the wrong error code.');
			return;
		}
		$this->fail('setComponentConfigurations() accepted an invalid configuration object without throwing an exception.');
	}
	
	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentConfigurationReturnsCloneOfConfiguration() {
		$configuration1 = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$configuration2 = $this->componentManager->getComponentConfiguration('T3_TestPackage_BasicClass');
		$this->assertNotSame($configuration1, $configuration2, 'getComponentConfiguration() did not return a clone but the same component configuration!');
	}

	/**
	 * Checks if the component manager registers component types (interfaces) correctly
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentTypeBasicallyWorks() {
		$implementation = $this->componentManager->getComponent('T3_TestPackage_SomeInterface');
		$this->assertType('T3_TestPackage_SomeImplementation', $implementation, 'The component of component type ...SomeInterface is not implemented by ...SomeImplementation!');
	}

	/**
	 * Checks if the class name of a component can be really set
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_setComponentClassNameWorksAsExpected() {
		$componentName = 'T3_TestPackage_BasicClass';
		$this->componentManager->setComponentClassName($componentName, 'T3_TestPackage_ReplacingClass');
		$component = $this->componentManager->getComponent($componentName);

		$this->assertEquals('T3_TestPackage_ReplacingClass', get_class($component), 'The component was not of the expected class.');
	}
}
?>