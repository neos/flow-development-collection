<?php
namespace TYPO3\Flow\Tests\Unit\Log;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the generic Logger
 *
 */
class LoggerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function logPassesItsArgumentsToTheBackendsAppendMethod()
    {
        $mockBackend = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend);
        $logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
    }

    /**
     * @test
     */
    public function addBackendAllowsForAddingMultipleBackends()
    {
        $mockBackend1 = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend1->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

        $mockBackend2 = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend2->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend1);
        $logger->addBackend($mockBackend2);
        $logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
    }

    /**
     * @test
     */
    public function addBackendRunsTheBackendsOpenMethod()
    {
        $mockBackend = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend->expects($this->once())->method('open');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend);
    }

    /**
     * @test
     */
    public function removeBackendRunsTheBackendsCloseMethodAndRemovesItFromTheLogger()
    {
        $mockBackend = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend->expects($this->once())->method('close');
        $mockBackend->expects($this->once())->method('append');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend);
        $logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

        $logger->removeBackend($mockBackend);
        $logger->log('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Log\Exception\NoSuchBackendException
     */
    public function removeThrowsAnExceptionOnTryingToRemoveABackendNotPreviouslyAdded()
    {
        $mockBackend = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->removeBackend($mockBackend);
    }

    /**
     * @test
     */
    public function theShutdownMethodRunsCloseOnAllRegisteredBackends()
    {
        $mockBackend1 = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend1->expects($this->once())->method('close');

        $mockBackend2 = $this->getMock('TYPO3\Flow\Log\Backend\BackendInterface', array('open', 'append', 'close'));
        $mockBackend2->expects($this->once())->method('close');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend1);
        $logger->addBackend($mockBackend2);
        $logger->shutdownObject();
    }
}
