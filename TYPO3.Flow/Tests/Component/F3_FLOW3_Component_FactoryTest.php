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
 * Testcase for the Component Factory
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FactoryTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if getComponent() returns the expected class type
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsCorrectClassType() {
		$testComponentInstance = $this->componentFactory->getComponent('F3::TestPackage::BasicClass');
		$this->assertTrue($testComponentInstance instanceof F3::TestPackage::BasicClass, 'Component instance is no instance of our basic test class!');
	}

	/**
	 * Checks if getComponent() fails on non-existing components
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentFailsOnNonExistentComponent() {
		try {
			$this->componentFactory->getComponent('F3::TestPackage::ThisClassDoesNotExist');
		} catch (F3::FLOW3::Component::Exception::UnknownComponent $exception) {
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
		$firstInstance = $this->componentFactory->getComponent('F3::TestPackage::BasicClass');
		$secondInstance = $this->componentFactory->getComponent('F3::TestPackage::BasicClass');
		$this->assertSame($secondInstance, $firstInstance, 'getComponent() did not return a truly unique instance when asked for a non-configured component.');
	}

	/**
	 * Checks if getComponent() delivers a prototype of a component which is configured as a prototype
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsPrototypeInstanceIfConfigured() {
		$firstInstance = $this->componentFactory->getComponent('F3::TestPackage::PrototypeClass');
		$secondInstance = $this->componentFactory->getComponent('F3::TestPackage::PrototypeClass');
		$this->assertNotSame($secondInstance, $firstInstance, 'getComponent() did not return a fresh prototype instance when asked for a component configured as prototype.');
	}

	/**
	 * Checks if getComponent() delivers the correct class if the class name is different from the component name
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentReturnsCorrectClassIfDifferentFromComponentName() {
		$component = $this->componentFactory->getComponent('F3::TestPackage::ClassToBeReplaced');
		$this->assertTrue($component instanceof F3::TestPackage::ReplacingClass, 'getComponent() did not return a the replacing class.');
	}

	/**
	 * Checks if getComponent() passes arguments to the constructor of a component class
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentPassesArgumentsToComponentClassConstructor() {
		$component = $this->componentFactory->getComponent('F3::TestPackage::ClassWithOptionalConstructorArguments', 'test1', 'test2', 'test3');
		$checkSucceeded = (
			$component->argument1 == 'test1' &&
			$component->argument2 == 'test2' &&
			$component->argument3 == 'test3'
		);
		$this->assertTrue($checkSucceeded, 'getComponent() did not instantiate the component with the specified constructor parameters.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorArgumentsPassedToGetComponentAreNotAddedToRealComponentConfiguration() {
		$componentName = 'F3::TestPackage::ClassWithOptionalConstructorArguments';
		$componentConfiguration = $this->componentManager->getComponentConfiguration($componentName);
		$componentConfiguration->setConstructorArguments(array());

		$this->componentManager->setComponentConfiguration($componentConfiguration);

		$component1 = $this->componentFactory->getComponent($componentName, 'theFirstArgument');
		$this->assertEquals('theFirstArgument', $component1->argument1, 'The constructor argument has not been set.');

		$component2 = $this->componentFactory->getComponent($componentName);

		$this->assertEquals('', $component2->argument1, 'The constructor argument1 is still not empty although no argument was passed to getComponent().');
		$this->assertEquals('', $component2->argument2, 'The constructor argument2 is still not empty although no argument was passed to getComponent().');
		$this->assertEquals('', $component2->argument3, 'The constructor argument3 is still not empty although no argument was passed to getComponent().');
	}
}
?>