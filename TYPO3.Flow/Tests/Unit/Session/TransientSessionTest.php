<?php
namespace TYPO3\FLOW3\Tests\Unit\Session;

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
 * Testcase for the Transient Session implementation
 *
 */
class TransientSessionTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theTransientSessionImplementsTheSessionInterface() {
		$session = new \TYPO3\FLOW3\Session\TransientSession();
		$this->assertInstanceOf('TYPO3\FLOW3\Session\SessionInterface', $session);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aSessionIdIsGeneratedOnStartingTheSession() {
		$session = new \TYPO3\FLOW3\Session\TransientSession();
		$session->start();
		$this->assertTrue(strlen($session->getId()) == 13);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tryingToGetTheSessionIdWithoutStartingTheSessionThrowsAnException() {
		$session = new \TYPO3\FLOW3\Session\TransientSession();
		$session->getId();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringsCanBeStoredByCallingPutData() {
		$session = new \TYPO3\FLOW3\Session\TransientSession();
		$session->start();
		$session->putData('theKey', 'some data');
		$this->assertEquals('some data', $session->getData('theKey'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allSessionDataCanBeFlushedByCallingDestroy() {
		$session = new \TYPO3\FLOW3\Session\TransientSession();
		$session->start();
		$session->putData('theKey', 'some data');
		$session->destroy();
		$this->assertNull($session->getData('theKey'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasKeyReturnsTrueOrFalseAccordingToAvailableKeys() {
		$session = new \TYPO3\FLOW3\Session\TransientSession();
		$session->start();
		$session->putData('theKey', 'some data');
		$this->assertTrue($session->hasKey('theKey'));
		$this->assertFalse($session->hasKey('noKey'));
	}
}


?>