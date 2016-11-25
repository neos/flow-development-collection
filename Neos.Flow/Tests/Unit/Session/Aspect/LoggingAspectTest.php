<?php
namespace Neos\Flow\Tests\Unit\Session\Aspect;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\JoinPoint;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Session\TransientSession;
use Neos\Flow\Session\Aspect\LoggingAspect;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Logging Aspect implementation
 */
class LoggingAspectTest extends UnitTestCase
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

        $mockJoinPoint = new JoinPoint($testSession, TransientSession::class, 'destroy', ['reason' => 'session timed out']);
        $mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
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

        $mockJoinPoint = new JoinPoint($testSession, TransientSession::class, 'destroy', []);
        $mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
        $mockSystemLogger
            ->expects($this->once())
            ->method('log')
            ->with($this->equalTo('TransientSession: Destroyed session with id ' . $testSessionId . ': no reason given'));

        $loggingAspect = new LoggingAspect();
        $this->inject($loggingAspect, 'systemLogger', $mockSystemLogger);
        $loggingAspect->logDestroy($mockJoinPoint);
    }
}
