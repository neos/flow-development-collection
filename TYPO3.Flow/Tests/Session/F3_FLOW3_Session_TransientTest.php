<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Session;

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
 * @subpackage Session
 * @version $Id$
 */

/**
 * Testcase for the Transient Session implementation
 *
 * @package FLOW3
 * @subpackage Session
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TransientTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theTransientSessionImplementsTheSessionInterface() {
		$session = new \F3\FLOW3\Session\Transient();
		$this->assertType('F3\FLOW3\Session\SessionInterface', $session);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aSessionIdIsGeneratedOnStartingTheSession() {
		$session = new \F3\FLOW3\Session\Transient();
		$session->start();
		$this->assertTrue(strlen($session->getID()) == 13);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tryingToGetTheSessionIdWithoutStartingTheSessionThrowsAnException() {
		$session = new \F3\FLOW3\Session\Transient();
		$session->getID();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringsCanBeStoredByCallingPutData() {
		$session = new \F3\FLOW3\Session\Transient();
		$session->start();
		$session->putData('theKey', 'some data');
		$this->assertEquals('some data', $session->getData('theKey'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allSessionDataCanBeFlushedByCallingDestroy() {
		$session = new \F3\FLOW3\Session\Transient();
		$session->start();
		$session->putData('theKey', 'some data');
		$session->destroy();
		$this->assertNull($session->getData('theKey'));
	}
}


?>