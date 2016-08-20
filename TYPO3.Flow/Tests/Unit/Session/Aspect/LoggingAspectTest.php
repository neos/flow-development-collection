<?php
namespace TYPO3\Flow\Tests\Unit\Session\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Aop\JoinPoint;
use TYPO3\Flow\Session\TransientSession;
use TYPO3\Flow\Session\Aspect\LoggingAspect;

/**
 * Testcase for the Logging Aspect implementation
 */
class LoggingAspectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Proofs correct logging behaviour
     * @test
     */
    public function logDestroyLogsSessionIdAndArgumentReason()
    {
        $testSession = new TransientSession();
        $testSession->start();
        $testSessionId = $testSession->getId();

        $mockJoinPoint = new JoinPoint($testSession, \TYPO3\Flow\Session\TransientSession::class, 'destroy', array('reason' => 'session timed out'));
        $mockSystemLogger = $this->createMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);
        $mockSystemLogger
            ->expects($this->once())
            ->method('log')
            ->with($this->equalTo('TransientSession: Destroyed session with id ' . $testSessionId . ': session timed out'), $this->equalTo(LOG_INFO));

        $loggingAspect = new LoggingAspect();
        $this->inject($loggingAspect, 'systemLogger', $mockSystemLogger);
        $loggingAspect->logDestroy($mockJoinPoint);
    }

    /**
     * Proofs correct logging behaviour without argument reason given
     *
     * @test
     */
    public function logDestroyDoesNotRequireArgumentReason()
    {
        $testSession = new TransientSession();
        $testSession->start();
        $testSessionId = $testSession->getId();

        $mockJoinPoint = new JoinPoint($testSession, \TYPO3\Flow\Session\TransientSession::class, 'destroy', array());
        $mockSystemLogger = $this->createMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);
        $mockSystemLogger
            ->expects($this->once())
            ->method('log')
            ->with($this->equalTo('TransientSession: Destroyed session with id ' . $testSessionId . ': no reason given'));

        $loggingAspect = new LoggingAspect();
        $this->inject($loggingAspect, 'systemLogger', $mockSystemLogger);
        $loggingAspect->logDestroy($mockJoinPoint);
    }
}
