<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Persistence;

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
 * Testcase for the Abstract Persistence Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractPersistenceManagerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\Tests\Unit\Persistence\AbstractPersistenceManager
	 */
	protected $abstractPersistenceManager;

	public function setUp() {
		$this->abstractPersistenceManager = $this->getMock('F3\FLOW3\Persistence\AbstractPersistenceManager', array('initialize', 'persistAll', 'isNewObject', 'getObjectByIdentifier', 'createQueryForType', 'add', 'remove', 'merge', 'getIdentifierByObject'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertObjectToIdentityArrayConvertsAnObject() {
		$someObject = new \stdClass();
		$this->abstractPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($someObject)->will($this->returnValue(123));

		$expectedResult = array('__identity' => 123);
		$actualResult = $this->abstractPersistenceManager->convertObjectToIdentityArray($someObject);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Persistence\Exception\UnknownObjectException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertObjectToIdentityArrayThrowsExceptionIfIdentityForTheGivenObjectCantBeDetermined() {
		$someObject = new \stdClass();
		$this->abstractPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($someObject)->will($this->returnValue(NULL));

		$this->abstractPersistenceManager->convertObjectToIdentityArray($someObject);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertObjectsToIdentityArraysRecursivelyConvertsObjects() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$this->abstractPersistenceManager->expects($this->at(0))->method('getIdentifierByObject')->with($object1)->will($this->returnValue('identifier1'));
		$this->abstractPersistenceManager->expects($this->at(1))->method('getIdentifierByObject')->with($object2)->will($this->returnValue('identifier2'));

		$originalArray = array('foo' => 'bar', 'object1' => $object1, 'baz' => array('object2' => $object2));
		$expectedResult = array('foo' => 'bar', 'object1' => array('__identity' => 'identifier1'), 'baz' => array('object2' => array('__identity' => 'identifier2')));

		$actualResult = $this->abstractPersistenceManager->convertObjectsToIdentityArrays($originalArray);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertObjectsToIdentityArraysConvertsObjectsInIterators() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$this->abstractPersistenceManager->expects($this->at(0))->method('getIdentifierByObject')->with($object1)->will($this->returnValue('identifier1'));
		$this->abstractPersistenceManager->expects($this->at(1))->method('getIdentifierByObject')->with($object2)->will($this->returnValue('identifier2'));

		$originalArray = array('foo' => 'bar', 'object1' => $object1, 'baz' => new \ArrayObject(array('object2' => $object2)));
		$expectedResult = array('foo' => 'bar', 'object1' => array('__identity' => 'identifier1'), 'baz' => array('object2' => array('__identity' => 'identifier2')));

		$actualResult = $this->abstractPersistenceManager->convertObjectsToIdentityArrays($originalArray);
		$this->assertEquals($expectedResult, $actualResult);
	}

}

?>
