<?php
namespace TYPO3\Flow\Tests\Unit\Session\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Aop\JoinPoint;
use TYPO3\Flow\Session\TransientSession;
use TYPO3\Flow\Session\Aspect\LoggingAspect;

/**
 * Testcase for the Logging Aspect implementation
 */
class LoggingAspectTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Proofs correct logging behaviour
	 * @test
	 */
	public function logDestroyLogsSessionIdAndArgumentReason() {
		$testSession = new TransientSession();
		$testSession->start();
		$testSessionId = $testSession->getId();

		$mockJoinPoint = new JoinPoint($testSession, 'TYPO3\Flow\Session\TransientSession', 'destroy', array('reason' => 'session timed out'));
		$mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$mockSystemLogger
			->expects($this->once())
			->method('log')
			->with($this->equalTo('Destroyed session with id ' . $testSessionId . ': session timed out'), $this->equalTo(LOG_DEBUG));

		$loggingAspect = new LoggingAspect();
		$this->inject($loggingAspect, 'systemLogger', $mockSystemLogger);
		$loggingAspect->logDestroy($mockJoinPoint);
	}

	/**
	 * Proofs correct logging behaviour without argument reason given
	 *
	 * @test
	 */
	public function logDestroyDoesNotRequireArgumentReason() {
		$testSession = new TransientSession();
		$testSession->start();
		$testSessionId = $testSession->getId();

		$mockJoinPoint = new JoinPoint($testSession, 'TYPO3\Flow\Session\TransientSession', 'destroy', array());
		$mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$mockSystemLogger
			->expects($this->once())
			->method('log')
			->with($this->equalTo('Destroyed session with id ' . $testSessionId . ': no reason given'));

		$loggingAspect = new LoggingAspect();
		$this->inject($loggingAspect, 'systemLogger', $mockSystemLogger);
		$loggingAspect->logDestroy($mockJoinPoint);
	}

}

?>