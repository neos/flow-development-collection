<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\CommandManager;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Cli\Request;
use Neos\Flow\Cli\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the Command Controller
 */
final class CommandControllerTest extends UnitTestCase
{
    /**
     * @var CommandController
     */
    protected $commandController;

    /**
     * @var ConsoleOutput|MockObject
     */
    protected $mockConsoleOutput;

    protected function setUp(): void
    {
        $this->commandController = $this->getAccessibleMock(CommandController::class, ['resolveCommandMethodName', 'callCommandMethod']);

        $mockCommandManager = $this->createMock(CommandManager::class);
        $mockCommandManager->method('getCommandMethodParameters')->willReturn(([]));
        $this->inject($this->commandController, 'commandManager', $mockCommandManager);

        $this->mockConsoleOutput = $this->createMock(ConsoleOutput::class);
        $this->inject($this->commandController, 'output', $this->mockConsoleOutput);
    }

    #[Test]
    public function processRequestThrowsExceptionIfGivenRequestIsNoCliRequest()
    {
        $this->expectException(\Error::class);
        $mockRequest = $this->createStub(ActionRequest::class);
        $mockResponse = new ActionResponse();

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    #[Test]
    public function processRequestMarksRequestDispatched()
    {
        $mockRequest = $this->createMock(Request::class);
        $mockResponse = $this->createStub(Response::class);

        $mockRequest->expects($this->once())->method('setDispatched')->with(true);

        $this->commandController->processRequest($mockRequest, $mockResponse);
    }

    #[Test]
    public function processRequestResetsCommandMethodArguments()
    {
        $mockRequest = $this->createStub(Request::class);
        $mockResponse = $this->createStub(Response::class);

        $mockArguments = new Arguments();
        $mockArguments->addNewArgument('foo');
        $this->inject($this->commandController, 'arguments', $mockArguments);

        self::assertCount(1, $this->commandController->_get('arguments'));
        $this->commandController->processRequest($mockRequest, $mockResponse);
        self::assertCount(0, $this->commandController->_get('arguments'));
    }

    #[Test]
    public function outputWritesGivenStringToTheConsoleOutput()
    {
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('some text');
        $this->commandController->_call('output', 'some text');
    }

    #[Test]
    public function outputReplacesArgumentsInGivenString()
    {
        $this->mockConsoleOutput->expects($this->once())->method('output')->with('%2$s %1$s', ['text', 'some']);
        $this->commandController->_call('output', '%2$s %1$s', ['text', 'some']);
    }
}
