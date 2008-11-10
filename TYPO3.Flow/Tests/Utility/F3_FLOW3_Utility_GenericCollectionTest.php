<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Utility;

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
 * @version $Id$
 */

require_once (FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_DummyClass.php');
require_once (FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_SecondDummyClass.php');

/**
 * Testcase for the Utility GenericCollection class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class GenericCollectionTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function genericCollectionIsPrototype() {
		$collection1 = $this->componentFactory->create('F3::FLOW3::Utility::GenericCollection', 'F3::FLOW3::Fixture::DummyClass');
		$collection2 = $this->componentFactory->create('F3::FLOW3::Utility::GenericCollection', 'F3::FLOW3::Fixture::DummyClass');
		$this->assertNotSame($collection1, $collection2, 'Obviously GenericCollection is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsZeroIfCollectionIsEmpty() {
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$this->assertEquals(0, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsCorrectValueIfCollectionContainsElements() {
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$collection->append(new F3::FLOW3::Fixture::DummyClass());
		$collection->append(new F3::FLOW3::Fixture::DummyClass());
		$this->assertEquals(2, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsCorrectValueAfterReplacingAnElement() {
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$collection[0] = new F3::FLOW3::Fixture::DummyClass();
		$this->assertEquals(1, $collection->count());
		$collection[0] = new F3::FLOW3::Fixture::DummyClass();
		$this->assertEquals(1, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsCorrectValueAfterUnsettingAnElement() {
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$collection[0] = new F3::FLOW3::Fixture::DummyClass();
		$collection[1] = new F3::FLOW3::Fixture::DummyClass();
		$this->assertEquals(2, $collection->count());
		unset($collection[0]);
		$this->assertEquals(1, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function currentReturnsFalseIfCollectionIsEmpty() {
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$this->assertFalse($collection->current());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function currentReturnsFirstElement() {
		$someObject = new F3::FLOW3::Fixture::DummyClass();
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$collection->append($someObject);
		$collection->append(new F3::FLOW3::Fixture::DummyClass());
		$this->assertSame($someObject, $collection->current());
	}

	/**
	 * @test
	 * @expectedException ::InvalidArgumentException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function appendingObjectOfDifferentTypeThrowsException() {
		$collection = new F3::FLOW3::Utility::GenericCollection('F3::FLOW3::Fixture::DummyClass');
		$collection->append(new F3::FLOW3::Fixture::SecondDummyClass());
	}

}
?>