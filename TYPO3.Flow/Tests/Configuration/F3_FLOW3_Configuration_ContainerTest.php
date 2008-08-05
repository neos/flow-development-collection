<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the configuration container class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Configuration_ContainerTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function simpleOptionCanBeAddedThroughSimpleAssignment() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->newOption = 'testValue';
		$this->assertEquals('testValue', $configuration->newOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFly() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->parentOption->childOption = 'the child';
		$this->assertEquals('the child', $configuration->parentOption->childOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyOnThirdLevel() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->parentOption->childOption->grandChildOption = 'the grand child';
		$this->assertEquals('the grand child', $configuration->parentOption->childOption->grandChildOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function optionValuesCanBeArrays() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->someOption = array(1, 2, 3);
		$configuration->firstLevel->anotherOption = array(4, 5, 6);
		$this->assertEquals(array(1, 2, 3), $configuration->someOption, 'The retrieved value was not as expected.');
		$this->assertEquals(array(4, 5, 6), $configuration->firstLevel->anotherOption, 'The retrieved value of the other option was not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function containerCanBeLocked() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->lock();
		$this->assertTrue($configuration->isLocked(), 'Container could not be locked.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function lockingTheContainerAlsoLocksAllSubContainers() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->subConfiguration->subSubConfiguration;
		$configuration->otherOption = array('x' => 'y');

		$configuration->lock();
		$this->assertTrue($configuration->subConfiguration->isLocked(), 'sub configuration is not locked');
		$this->assertTrue($configuration->subConfiguration->subSubConfiguration->isLocked(), 'sub sub configuration is not locked');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function gettingOptionsFromLockedContainerIsAllowed() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->someOption = 'some value';
		$configuration->lock();
		$this->assertEquals('some value', $configuration->someOption, 'Could not retrieve the option.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function settingOptionsOnLockedContainerResultsInException() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->lock();
		try {
			$configuration->someOption = 'some value';
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Configuration_Exception_ContainerIsLocked $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function foreachCanTraverseOverFirstLevelOptions() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->firstOption = '1';
		$configuration->secondOption = '2';
		$configuration->thirdOption = '3';

		$keys = '';
		$values = '';
		foreach ($configuration as $key => $value) {
			$keys .= $key;
			$values .= $value;
		}
		$this->assertEquals('firstOptionsecondOptionthirdOption', $keys, 'Keys did not match.');
		$this->assertEquals('123', $values, 'Values did not match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function issetReturnsTheCorrectResult() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->someOption = 'some value';
		$this->assertTrue(isset($configuration->someOption), 'isset() did not return TRUE.');
		$this->assertFalse(isset($configuration->otherOption), 'isset() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetReallyUnsetsOption() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->someOption = 'some value';
		unset($configuration->someOption);
		$this->assertFalse(isset($configuration->someOption), 'isset() returned TRUE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeJustAddsNonConflictingOptionsToTheExistingContainer() {
		$configurationA = new F3_FLOW3_Configuration_Container();
		$configurationA->firstOption = 'firstValue';
		$configurationB = new F3_FLOW3_Configuration_Container();
		$configurationB->secondOption = 'secondValue';

		$expectedConfiguration = new F3_FLOW3_Configuration_Container();
		$expectedConfiguration->firstOption = 'firstValue';
		$expectedConfiguration->secondOption = 'secondValue';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeAlsoMergesNonConflictingOptionsOfSubContainers() {
		$configurationA = new F3_FLOW3_Configuration_Container();
		$configurationA->a->aSub = 'aaSub';
		$configurationA->c = 'c';
		$configurationB = new F3_FLOW3_Configuration_Container();
		$configurationB->a->bSub = 'abSub';
		$configurationB->d = 'd';

		$expectedConfiguration = new F3_FLOW3_Configuration_Container();
		$expectedConfiguration->a->aSub = 'aaSub';
		$expectedConfiguration->c = 'c';
		$expectedConfiguration->a->bSub = 'abSub';
		$expectedConfiguration->d = 'd';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeCanMergeTwoContainersRecursivelyWithConflictingOptions() {
		$configurationA = new F3_FLOW3_Configuration_Container();
		$configurationA->a->aSub = 'oldA';
		$configurationA->a->aSubB = 'oldSubB';
		$configurationA->b = 'oldB';
		$configurationB = new F3_FLOW3_Configuration_Container();
		$configurationB->a->aSub = 'newA';
		$configurationB->b = 'newB';

		$expectedConfiguration = new F3_FLOW3_Configuration_Container();
		$expectedConfiguration->a->aSub = 'newA';
		$expectedConfiguration->a->aSubB = 'oldSubB';
		$expectedConfiguration->b = 'newB';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeCanHandleNestedContainersWithMoreThanTwoLevels() {
		$configurationA = new F3_FLOW3_Configuration_Container();
		$configurationA->a->aa->aaa = 'oldAAA';
		$configurationA->a->ab = 'oldAB';
		$configurationA->a->aa->aab->aaba->aabaa = 'oldAABAA';
		$configurationA->b = 'oldB';

		$configurationB = new F3_FLOW3_Configuration_Container();
		$configurationB->a->aa->aaa = 'newAAA';
		$configurationB->a->aa->aab->aabb = 'newAABB';

		$expectedConfiguration = new F3_FLOW3_Configuration_Container();
		$expectedConfiguration->a->aa->aaa = 'newAAA';
		$expectedConfiguration->a->ab = 'oldAB';
		$expectedConfiguration->a->aa->aab->aaba->aabaa = 'oldAABAA';
		$expectedConfiguration->a->aa->aab->aabb = 'newAABB';
		$expectedConfiguration->b = 'oldB';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeDoesNotTryToMergeAContainerWithAnArray() {
		$configurationA = new F3_FLOW3_Configuration_Container();
		$configurationA->parent->children = array('a' => 'A');

		$configurationB = new F3_FLOW3_Configuration_Container();
		$configurationB->parent->children->a = 'A';

		$expectedConfiguration = new F3_FLOW3_Configuration_Container();
		$expectedConfiguration->parent->children->a = 'A';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingNonExistingMethodResultsInException() {
		$configuration = new F3_FLOW3_Configuration_Container();
		try {
			$configuration->nonExistingMethod();
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Configuration_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function passingNoArgumentToMagicSetterResultsInException() {
		$configuration = new F3_FLOW3_Configuration_Container();
		try {
			$configuration->setOption();
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Configuration_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function passingTwoArgumentToMagicSetterResultsInException() {
		$configuration = new F3_FLOW3_Configuration_Container();
		try {
			$configuration->setOption('argument1', 'argument2');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Configuration_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function simpleOptionCanBeAddedThroughMagicSetter() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->setNewOption('testValue');
		$this->assertEquals('testValue', $configuration->newOption);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyThroughMagicSetter() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->parentOption->setChildOption('the child');
		$this->assertEquals('the child', $configuration->parentOption->childOption);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyOnThirdLevelThroughMagicSetter() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->parentOption->childOption->setGrandChildOption('the grand child');
		$this->assertEquals('the grand child', $configuration->parentOption->childOption->grandChildOption);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function magicSetterReturnsItself() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$this->assertSame($configuration, $configuration->setNewOption('testValue'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function optionsCanBeAddedThroughChainingSyntax() {
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration
			->setOption1('value1')
			->setOption2('value2')
			->setOption3('value3');
		$this->assertEquals('value1', $configuration->option1);
		$this->assertEquals('value2', $configuration->option2);
		$this->assertEquals('value3', $configuration->option3);
	}
}
?>