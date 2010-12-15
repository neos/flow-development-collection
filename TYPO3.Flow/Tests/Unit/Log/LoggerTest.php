<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Log;

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
 * Testcase for the generic Logger
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LoggerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logPassesItsArgumentsToTheBackendsAppendMethod() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
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
		$mockBackend1 = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend1->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$mockBackend2 = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
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
		$mockBackend = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('open');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeBackendRunsTheBackendsCloseMethodAndRemovesItFromTheLogger() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
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
	 * @expectedException \F3\FLOW3\Log\Exception\NoSuchBackendException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeThrowsAnExceptionOnTryingToRemoveABackendNotPreviouslyAdded() {
		$mockBackend = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->removeBackend($mockBackend);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theShutdownMethodRunsCloseOnAllRegisteredBackends() {
		$mockBackend1 = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend1->expects($this->once())->method('close');

		$mockBackend2 = $this->getMock('F3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend2->expects($this->once())->method('close');

		$logger = new \F3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend1);
		$logger->addBackend($mockBackend2);
		$logger->shutdownObject();
	}
}
?>