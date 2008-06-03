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
 * @version $Id$
 */

/**
 * Testcase for the base Repository
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Persistence_RepositoryTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$repository = new F3_FLOW3_Persistence_Repository;
		$this->assertTrue($repository instanceof F3_FLOW3_Persistence_RepositoryInterface);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new stdClass();
		$repository = new F3_FLOW3_Persistence_Repository();
		$repository->add($someObject);
		$this->assertAttributeSame(array(spl_object_hash($someObject) => $someObject), 'objects', $repository);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = new stdClass();
		$object2 = new stdClass();
		$object3 = new stdClass();

		$repository = new F3_FLOW3_Persistence_Repository();
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
		$object1 = new ArrayObject(array('val' => '1'));
		$object2 = new ArrayObject(array('val' => '2'));
		$object3 = new ArrayObject(array('val' => '3'));

		$repository = new F3_FLOW3_Persistence_Repository();
		$repository->add($object1);
		$repository->add($object2);
		$repository->add($object3);

		$object2['foo'] = 'bar';
		$object3['val'] = '2';

		$repository->remove($object2);
		$this->assertAttributeSame(array(spl_object_hash($object1) => $object1, spl_object_hash($object3) => $object3), 'objects', $repository);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findAllReturnsAllPreviouslyAddedObjects() {
		$objects = array(new stdClass(), new stdClass(), new stdClass());
		$repository = new F3_FLOW3_Persistence_Repository();
		foreach ($objects as $object) {
			$repository->add($object);
		}
		$this->assertSame($objects, $repository->findAll());
	}
}

?>