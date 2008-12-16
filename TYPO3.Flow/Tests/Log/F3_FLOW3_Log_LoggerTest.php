<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log;

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
 * @subpackage Log
 * @version $Id$
 */

/**
 * Testcase for the generic Logger
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class LoggerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logPassesItsArgumentsToTheBackendsAppendMethod() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addBackendAllowsForAddingMultipleBackends() {
		$mockBackend1 = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend1->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$mockBackend2 = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend2->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend1);
		$logger->addBackend($mockBackend2);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addBackendRunsTheBackendsOpenMethod() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('open');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeBackendRunsTheBackendsCloseMethodAndRemovesItFromTheLogger() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('close');
		$mockBackend->expects($this->once())->method('append');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$logger->removeBackend($mockBackend);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Log\Exception\NoSuchBackend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeThrowsAnExceptionOnTryingToRemoveABackendNotPreviouslyAdded() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->removeBackend($mockBackend);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDestructorRunsCloseOnAllRegisteredBackends() {
		$mockBackend1 = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend1->expects($this->once())->method('close');

		$mockBackend2 = $this->getMock('F3\FLOW3\Log\BackendInterface', array('open', 'append', 'close'));
		$mockBackend2->expects($this->once())->method('close');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend1);
		$logger->addBackend($mockBackend2);
		unset($logger);
	}
}
?>