<?php
namespace TYPO3\Flow\Tests\Unit\Session;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Transient Session implementation
 *
 */
class TransientSessionTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theTransientSessionImplementsTheSessionInterface() {
		$session = new \TYPO3\Flow\Session\TransientSession();
		$this->assertInstanceOf('TYPO3\Flow\Session\SessionInterface', $session);
	}

	/**
	 * @test
	 */
	public function aSessionIdIsGeneratedOnStartingTheSession() {
		$session = new \TYPO3\Flow\Session\TransientSession();
		$session->start();
		$this->assertTrue(strlen($session->getId()) == 13);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function tryingToGetTheSessionIdWithoutStartingTheSessionThrowsAnException() {
		$session = new \TYPO3\Flow\Session\TransientSession();
		$session->getId();
	}

	/**
	 * @test
	 */
	public function stringsCanBeStoredByCallingPutData() {
		$session = new \TYPO3\Flow\Session\TransientSession();
		$session->start();
		$session->putData('theKey', 'some data');
		$this->assertEquals('some data', $session->getData('theKey'));
	}

	/**
	 * @test
	 */
	public function allSessionDataCanBeFlushedByCallingDestroy() {
		$session = new \TYPO3\Flow\Session\TransientSession();
		$session->start();
		$session->putData('theKey', 'some data');
		$session->destroy();
		$session->start();
		$this->assertNull($session->getData('theKey'));
	}

	/**
	 * @test
	 */
	public function hasKeyReturnsTrueOrFalseAccordingToAvailableKeys() {
		$session = new \TYPO3\Flow\Session\TransientSession();
		$session->start();
		$session->putData('theKey', 'some data');
		$this->assertTrue($session->hasKey('theKey'));
		$this->assertFalse($session->hasKey('noKey'));
	}
}


?>