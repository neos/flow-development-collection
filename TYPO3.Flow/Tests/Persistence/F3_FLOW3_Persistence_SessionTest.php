<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * Testcase for the Persistence Session
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class SessionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function objectRegisteredWithRegisterReconstitutedObjectCanBeRetrievedWithGetReconstitutedObjects() {
		$someObject = new \ArrayObject();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerReconstitutedObject($someObject);

		$reconstitutedObjects = $session->getReconstitutedObjects();
		$this->assertSame($someObject, array_pop($reconstitutedObjects));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterReconstitutedObjectRemovesObjectFromSession() {
		$someObject = new \ArrayObject();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerReconstitutedObject($someObject);
		$session->unregisterReconstitutedObject($someObject);

		$reconstitutedObjects = $session->getReconstitutedObjects();
		$this->assertSame(array(), $reconstitutedObjects);
	}


}
?>