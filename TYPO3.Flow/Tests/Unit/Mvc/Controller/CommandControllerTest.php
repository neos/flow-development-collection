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
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Flow\Cli\Request;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Mvc;

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
     * @var ReflectionService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockReflectionService;

    /**
     * @var ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConsoleOutput;

    public function setUp()
    {
        $this->commandController = $this->getAccessibleMock(CommandController::class, ['resolveCommandMethodName', 'callCommandMethod']);

        $this->mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->will($this->returnValue([]));
        $this->inject($this->commandController, 'reflectionService', $this->mockReflectionService);

        $this->mockConsoleOutput = $this->getMockBuilder(ConsoleOutput::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->commandController, 'output', $this->mockConsoleOutput);
    }


    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function processRequestThrowsExceptionIfGivenRequestIsNoCliRequest()
    {
        $mockRequest = $this->getMockBuilder(Mvc\RequestInterface::class)->getMock();
        $mockResponse = $this->getMockBuilder(Mvc\ResponseInterface::class)->getMock();

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestMarksRequestDispatched()
    {
        $mockRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder(Mvc\ResponseInterface::class)->getMock();

        $mockRequest->expects($this->once())->method('setDispatched')->with(true);

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestResetsCommandMethodArguments()
    {
        $mockRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder(Mvc\ResponseInterface::class)->getMock();

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
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('%2$s %1$s', ['text', 'some']);
        $this->commandController->_call('output', '%2$s %1$s', ['text', 'some']);
    }
}
