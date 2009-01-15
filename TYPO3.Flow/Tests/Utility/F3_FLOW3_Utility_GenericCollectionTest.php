<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the Utility GenericCollection class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class GenericCollectionTest extends \F3\Testing\BaseTestCase {

	public function setUp() {
		if (!class_exists('F3\Virtual\DummyClass', FALSE)) {
			eval('namespace F3\Virtual; class DummyClass {} class SecondDummyClass {}');
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsZeroIfCollectionIsEmpty() {
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$this->assertEquals(0, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsCorrectValueIfCollectionContainsElements() {
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$collection->append(new \F3\Virtual\DummyClass());
		$collection->append(new \F3\Virtual\DummyClass());
		$this->assertEquals(2, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsCorrectValueAfterReplacingAnElement() {
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$collection[0] = new \F3\Virtual\DummyClass();
		$this->assertEquals(1, $collection->count());
		$collection[0] = new \F3\Virtual\DummyClass();
		$this->assertEquals(1, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countReturnsCorrectValueAfterUnsettingAnElement() {
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$collection[0] = new \F3\Virtual\DummyClass();
		$collection[1] = new \F3\Virtual\DummyClass();
		$this->assertEquals(2, $collection->count());
		unset($collection[0]);
		$this->assertEquals(1, $collection->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function currentReturnsFalseIfCollectionIsEmpty() {
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$this->assertFalse($collection->current());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function currentReturnsFirstElement() {
		$someObject = new \F3\Virtual\DummyClass();
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$collection->append($someObject);
		$collection->append(new \F3\Virtual\DummyClass());
		$this->assertSame($someObject, $collection->current());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function appendingObjectOfDifferentTypeThrowsException() {
		$collection = new \F3\FLOW3\Utility\GenericCollection('F3\Virtual\DummyClass');
		$collection->append(new \F3\Virtual\SecondDummyClass());
	}

}
?>