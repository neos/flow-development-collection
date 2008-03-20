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
 * @version $Id:T3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the configuration container class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Configuration_ContainerTest extends T3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function simpleOptionCanBeAddedThroughSimpleAssignment() {
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->newOption = 'testValue';
		$this->assertEquals('testValue', $configuration->newOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFly() {
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->parentOption->childOption = 'the child';
		$this->assertEquals('the child', $configuration->parentOption->childOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyOnThirdLevel() {
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->parentOption->childOption->grandChildOption = 'the grand child';
		$this->assertEquals('the grand child', $configuration->parentOption->childOption->grandChildOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function optionValuesCanBeArrays() {
		$configuration = new T3_FLOW3_Configuration_Container();
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
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->lock();
		$this->assertTrue($configuration->isLocked(), 'Container could not be locked.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function gettingOptionsFromLockedContainerIsAllowed() {
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->someOption = 'some value';
		$configuration->lock();
		$this->assertEquals('some value', $configuration->someOption, 'Could not retrieve the option.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function settingOptionsOnLockedContainerResultsInException() {
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->lock();
		try {
			$configuration->someOption = 'some value';
			$this->fail('No exception was thrown.');
		} catch (T3_FLOW3_Configuration_Exception_ContainerIsLocked $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function foreachCanTraverseOverFirstLevelOptions() {
		$configuration = new T3_FLOW3_Configuration_Container();
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
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->someOption = 'some value';
		$this->assertTrue(isset($configuration->someOption), 'isset() did not return TRUE.');
		$this->assertFalse(isset($configuration->otherOption), 'isset() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetReallyUnsetsOption() {
		$configuration = new T3_FLOW3_Configuration_Container();
		$configuration->someOption = 'some value';
		unset($configuration->someOption);
		$this->assertFalse(isset($configuration->someOption), 'isset() returned TRUE.');
	}
}
?>