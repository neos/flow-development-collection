<?php
namespace TYPO3\Flow\Tests\Unit\Log;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $mockBackend = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
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
        $mockBackend1 = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
        $mockBackend1->expects($this->once())->method('append')->with('theMessage', 2, array('foo'), 'Foo', 'Bar', 'Baz');

        $mockBackend2 = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
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
        $mockBackend = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
        $mockBackend->expects($this->once())->method('open');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend);
    }

    /**
     * @test
     */
    public function removeBackendRunsTheBackendsCloseMethodAndRemovesItFromTheLogger()
    {
        $mockBackend = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
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
        $mockBackend = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->removeBackend($mockBackend);
    }

    /**
     * @test
     */
    public function theShutdownMethodRunsCloseOnAllRegisteredBackends()
    {
        $mockBackend1 = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
        $mockBackend1->expects($this->once())->method('close');

        $mockBackend2 = $this->getMockBuilder('TYPO3\Flow\Log\Backend\BackendInterface')->setMethods(array('open', 'append', 'close'))->getMock();
        $mockBackend2->expects($this->once())->method('close');

        $logger = new \TYPO3\Flow\Log\Logger();
        $logger->addBackend($mockBackend1);
        $logger->addBackend($mockBackend2);
        $logger->shutdownObject();
    }
}
