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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Testcase for the Component Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if getContext() returns the "Development" context if nothing else has been defined.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContextReturnsDefaultContext() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$componentManager = new F3::FLOW3::Component::Manager($mockReflectionService);
		$this->assertEquals('Development', $componentManager->getContext(), 'getContext() did not return "Development".');
	}

	/**
	 * Checks if setting and retrieving the context delivers the expected results
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContextBasicallyWorks() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$componentManager = new F3::FLOW3::Component::Manager($mockReflectionService);
		$componentManager->setContext('halululu');
		$this->assertEquals('halululu', $componentManager->getContext(), 'getContext() did not return the context we set.');

	}

	/**
	 * Checks if registerComponent() can register valid and unspectactular classes
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentCanRegisterNormalClasses() {
		$reflectionService = $this->componentManager->getComponent('F3::FLOW3::Reflection::Service');
		$componentManager = new F3::FLOW3::Component::Manager($reflectionService);
		$this->assertEquals($componentManager->isComponentRegistered('F3::TestPackage::BasicClass'), FALSE, 'isComponentRegistered() did not return FALSE although component is not yet registered.');
		$componentManager->registerComponent('F3::TestPackage::BasicClass');
		$this->assertTrue($componentManager->isComponentRegistered('F3::TestPackage::BasicClass'), 'isComponentRegistered() did not return TRUE although component has been registered.');
	}

	/**
	 * Checks if registerComponent() can register classes in sub directories to the
	 * Classes/ directory.
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentCanRegisterClassesInSubDirectories() {
		$reflectionService = $this->componentManager->getComponent('F3::FLOW3::Reflection::Service');
		$componentManager = new F3::FLOW3::Component::Manager($reflectionService);
		$this->assertFalse($componentManager->isComponentRegistered('F3::TestPackage::BasicClass'), 'isComponentRegistered() did not return FALSE although component is not yet registered.');
		$this->assertFalse($componentManager->isComponentRegistered('F3::TestPackage::SubDirectory::ClassInSubDirectory'), 'isComponentRegistered() did not return FALSE although component is not yet registered.');
		$componentManager->registerComponent('F3::TestPackage::SubDirectory::ClassInSubDirectory');
		$this->assertTrue($this->componentManager->isComponentRegistered('F3::TestPackage::SubDirectory::ClassInSubDirectory'), 'isComponentRegistered() did not return TRUE although component has been registered.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentRejectsAbstractClasses() {
		$reflectionService = $this->componentManager->getComponent('F3::FLOW3::Reflection::Service');
		$componentManager = new F3::FLOW3::Component::Manager($reflectionService);
		$this->assertFalse($componentManager->isComponentRegistered('F3::TestPackage::AbstractClass'), 'isComponentRegistered() did not return FALSE although the abstract class is not yet registered.');
		try {
			$componentManager->registerComponent('F3::TestPackage::AbstractClass');
			$this->fail('The component manager did not reject the registration of an abstract class.');
		} catch (F3::FLOW3::Component::Exception::InvalidClass $exception) {
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
			$this->componentManager->unregisterComponent('F3::NonExistentPackage::NonExistentClass');
		} catch (F3::FLOW3::Component::Exception::UnknownComponent $exception) {
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
		$this->assertEquals($this->componentManager->isComponentRegistered('F3::TestPackage::BasicClass'), TRUE, 'F3::TestPackage::BasicClass is not a registered component.');
		$this->componentManager->unregisterComponent('F3::TestPackage::BasicClass');
		$this->assertEquals($this->componentManager->isComponentRegistered('F3::TestPackage::BasicClass'), FALSE, 'isComponentRegistered() did not return FALSE although component should not be registered anymore.');
	}

	/**
	 * Checks if setComponentConfigurations() throws an exception if the configuration is no valid configuration object
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function setComponentConfigurationsThrowsExceptionForNonArray() {
		try {
			$this->componentManager->setComponentconfigurations(array('F3::TestPackage::BasicClass' => 'Some string'));
		} catch (::Exception $exception) {
			$this->assertEquals(1167826954, $exception->getCode(), 'setComponentConfigurations() throwed an exception but returned the wrong error code.');
			return;
		}
		$this->fail('setComponentConfigurations() accepted an invalid configuration object without throwing an exception.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setComponentConfigurationsRegistersYetUnknownComponentsFromComponentConfiguration() {
		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		$componentConfigurations['F3::TestPackage::SomeNonExistingComponent'] = new F3::FLOW3::Component::Configuration('F3::TestPackage::SomeNonExistingComponent', __CLASS__);
		$this->componentManager->setComponentConfigurations($componentConfigurations);
		$this->assertTrue($this->componentManager->isComponentRegistered('F3::TestPackage::SomeNonExistingComponent'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentConfigurationReturnsCloneOfConfiguration() {
		$configuration1 = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$configuration2 = $this->componentManager->getComponentConfiguration('F3::TestPackage::BasicClass');
		$this->assertNotSame($configuration1, $configuration2, 'getComponentConfiguration() did not return a clone but the same component configuration!');
	}

	/**
	 * Checks if the component manager registers component types (interfaces) correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerComponentTypeBasicallyWorks() {
		$implementation = $this->componentManager->getComponent('F3::TestPackage::SomeInterface');
		$this->assertType('F3::TestPackage::SomeImplementation', $implementation, 'The component of component type ...SomeInterface is not implemented by ...SomeImplementation!');
	}

	/**
	 * Checks if the class name of a component can be really set
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setComponentClassNameWorksAsExpected() {
		$componentName = 'F3::TestPackage::BasicClass';
		$this->componentManager->setComponentClassName($componentName, 'F3::TestPackage::ReplacingClass');
		$component = $this->componentManager->getComponent($componentName);

		$this->assertEquals('F3::TestPackage::ReplacingClass', get_class($component), 'The component was not of the expected class.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredComponentsReturnsArrayOfMixedCaseAndLowerCaseComponentNames() {
		$registeredComponents = $this->componentManager->getRegisteredComponents();
		$this->assertTrue(is_array($registeredComponents), 'The result is not an array.');
		foreach ($registeredComponents as $mixedCase => $lowerCase) {
			$this->assertTrue(strlen($mixedCase) > 0, 'The component name was an empty string.');
			$this->assertTrue(strtolower($mixedCase) == $lowerCase, 'The key and value were not equal after strtolower().');
		}
	}
}
?>