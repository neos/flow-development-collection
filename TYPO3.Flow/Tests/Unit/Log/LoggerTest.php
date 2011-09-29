<?php
namespace TYPO3\FLOW3\Tests\Unit\Log;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the generic Logger
 *
 */
class LoggerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logPassesItsArgumentsToTheBackendsAppendMethod() {
		$mockBackend = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$logger = new \TYPO3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addBackendAllowsForAddingMultipleBackends() {
		$mockBackend1 = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend1->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$mockBackend2 = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend2->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$logger = new \TYPO3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend1);
		$logger->addBackend($mockBackend2);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addBackendRunsTheBackendsOpenMethod() {
		$mockBackend = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('open');

		$logger = new \TYPO3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeBackendRunsTheBackendsCloseMethodAndRemovesItFromTheLogger() {
		$mockBackend = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend->expects($this->once())->method('close');
		$mockBackend->expects($this->once())->method('append');

		$logger = new \TYPO3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

		$logger->removeBackend($mockBackend);
		$logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Log\Exception\NoSuchBackendException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeThrowsAnExceptionOnTryingToRemoveABackendNotPreviouslyAdded() {
		$mockBackend = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));

		$logger = new \TYPO3\FLOW3\Log\Logger();
		$logger->removeBackend($mockBackend);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theShutdownMethodRunsCloseOnAllRegisteredBackends() {
		$mockBackend1 = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend1->expects($this->once())->method('close');

		$mockBackend2 = $this->getMock('TYPO3\FLOW3\Log\Backend\BackendInterface', array('open', 'append', 'close'));
		$mockBackend2->expects($this->once())->method('close');

		$logger = new \TYPO3\FLOW3\Log\Logger();
		$logger->addBackend($mockBackend1);
		$logger->addBackend($mockBackend2);
		$logger->shutdownObject();
	}
}
?>