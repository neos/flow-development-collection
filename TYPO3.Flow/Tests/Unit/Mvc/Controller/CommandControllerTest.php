<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Cli\CommandManager;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Command Controller
 */
class CommandControllerTest extends UnitTestCase
{
    /**
     * @var CommandController
     */
    protected $commandController;

    /**
     * @var CommandManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCommandManager;

    /**
     * @var \TYPO3\Flow\Cli\ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConsoleOutput;

    public function setUp()
    {
        $this->commandController = $this->getAccessibleMock(\TYPO3\Flow\Cli\CommandController::class, array('resolveCommandMethodName', 'callCommandMethod'));

        $this->mockCommandManager = $this->getMockBuilder(CommandManager::class)->disableOriginalConstructor()->getMock();
        $this->mockCommandManager->expects($this->any())->method('getCommandMethodParameters')->will($this->returnValue(array()));
        $this->inject($this->commandController, 'commandManager', $this->mockCommandManager);

        $this->mockConsoleOutput = $this->getMockBuilder(\TYPO3\Flow\Cli\ConsoleOutput::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->commandController, 'output', $this->mockConsoleOutput);
    }


    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function processRequestThrowsExceptionIfGivenRequestIsNoCliRequest()
    {
        $mockRequest = $this->createMock(\TYPO3\Flow\Mvc\RequestInterface::class);
        $mockResponse = $this->createMock(\TYPO3\Flow\Mvc\ResponseInterface::class);

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestMarksRequestDispatched()
    {
        $mockRequest = $this->getMockBuilder(\TYPO3\Flow\Cli\Request::class)->disableOriginalConstructor()->getMock();
        $mockResponse = $this->createMock(\TYPO3\Flow\Mvc\ResponseInterface::class);

        $mockRequest->expects($this->once())->method('setDispatched')->with(true);

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestResetsCommandMethodArguments()
    {
        $mockRequest = $this->getMockBuilder(\TYPO3\Flow\Cli\Request::class)->disableOriginalConstructor()->getMock();
        $mockResponse = $this->createMock(\TYPO3\Flow\Mvc\ResponseInterface::class);

        $mockArguments = new Arguments();
        $mockArguments->addNewArgument('foo');
        $this->inject($this->commandController, 'arguments', $mockArguments);

        $this->assertCount(1, $this->commandController->_get('arguments'));
        $this->commandController->processRequest($mockRequest, $mockResponse);
        $this->assertCount(0, $this->commandController->_get('arguments'));
    }

    /**
     * @test
     */
    public function outputWritesGivenStringToTheConsoleOutput()
    {
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('some text');
        $this->commandController->_call('output', 'some text');
    }

    /**
     * @test
     */
    public function outputReplacesArgumentsInGivenString()
    {
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('%2$s %1$s', array('text', 'some'));
        $this->commandController->_call('output', '%2$s %1$s', array('text', 'some'));
    }
}
