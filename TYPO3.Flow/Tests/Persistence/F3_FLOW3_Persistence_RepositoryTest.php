<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * Testcase for the base Repository
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RepositoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$repository = new \F3\FLOW3\Persistence\Repository;
		$this->assertTrue($repository instanceof \F3\FLOW3\Persistence\RepositoryInterface);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new \stdClass();
		$repository = new \F3\FLOW3\Persistence\Repository();
		$repository->add($someObject);
		$this->assertAttributeSame(array(spl_object_hash($someObject) => $someObject), 'objects', $repository);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$object3 = new \stdClass();

		$repository = new \F3\FLOW3\Persistence\Repository();
		$repository->add($object1);
		$repository->add($object2);
		$repository->add($object3);

		$repository->remove($object2);
		$this->assertAttributeSame(array(spl_object_hash($object1) => $object1, spl_object_hash($object3) => $object3), 'objects', $repository);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$object1 = new \ArrayObject(array('val' => '1'));
		$object2 = new \ArrayObject(array('val' => '2'));
		$object3 = new \ArrayObject(array('val' => '3'));

		$repository = new \F3\FLOW3\Persistence\Repository();
		$repository->add($object1);
		$repository->add($object2);
		$repository->add($object3);

		$object2['foo'] = 'bar';
		$object3['val'] = '2';

		$repository->remove($object2);
		$this->assertAttributeSame(array(spl_object_hash($object1) => $object1, spl_object_hash($object3) => $object3), 'objects', $repository);
	}

	/**
	 * Make sure we remember the objects that are not currently add()ed
	 * but might be in persistent storage.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeRetainsObjectForObjectsNotInCurrentSession() {
		$object = new \ArrayObject(array('val' => '1'));
		$repository = new \F3\FLOW3\Persistence\Repository();
		$repository->remove($object);
		$this->assertEquals(array(spl_object_hash($object) => $object), $repository->getRemovedObjects());
	}
}

?>