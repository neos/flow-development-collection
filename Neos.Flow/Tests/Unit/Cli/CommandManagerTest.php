<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Cli;
use Neos\Flow\Cli\Command;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\CommandManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Exception\AmbiguousCommandIdentifierException;
use Neos\Flow\Mvc\Exception\NoSuchCommandException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Unit\Cli\Fixtures\Command\MockACommandController;
use Neos\Flow\Tests\Unit\Cli\Fixtures\Command\MockBCommandController;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

require_once('Fixtures/Command/MockCommandController.php');

/**
 * Testcase for the CLI CommandManager class
 */
final class CommandManagerTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    /**
     * @var Cli\CommandManager
     */
    protected $commandManager;

    protected function setUp(): void
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with([ReflectionService::class])->willReturn($this->mockReflectionService);
        $this->commandManager = $this->getMockBuilder(Cli\CommandManager::class)->onlyMethods(['getAvailableCommands'])
            ->setConstructorArgs([$mockBootstrap, $mockObjectManager])
            ->getMock();
    }

    #[Test]
    public function getAvailableCommandsReturnsAllAvailableCommands(): void
    {
        $mockCommandControllerClassNames = [MockACommandController::class, MockBCommandController::class];
        $this->mockReflectionService->expects(self::once())->method('getAllSubClassNamesForClass')->with(CommandController::class)->willReturn($mockCommandControllerClassNames);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $mockObjectManager->method('getObjectNameByClassName')->willReturnArgument(0);
        $mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $commandManager = new CommandManager($mockBootstrap, $mockObjectManager);

        $commands = $commandManager->getAvailableCommands();
        self::assertCount(3, $commands);
        self::assertEquals('neos.flow.tests.unit.cli.fixtures:mocka:foo', $commands[0]->getCommandIdentifier());
        self::assertEquals('neos.flow.tests.unit.cli.fixtures:mocka:bar', $commands[1]->getCommandIdentifier());
        self::assertEquals('neos.flow.tests.unit.cli.fixtures:mockb:baz', $commands[2]->getCommandIdentifier());
    }

    #[Test]
    public function getCommandByIdentifierReturnsCommandIfIdentifierIsEqual()
    {
        $mockCommand = $this->createMock(Command::class);
        $mockCommand->expects($this->once())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
    }

    #[Test]
    public function getCommandByIdentifierWorksCaseInsensitive()
    {
        $mockCommand = $this->createMock(Command::class);
        $mockCommand->expects($this->once())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   Package.Key:conTroLler:Command  '));
    }

    #[Test]
    public function getCommandByIdentifierAllowsThePackageKeyToOnlyContainTheLastPartOfThePackageNamespaceIfCommandsAreUnambiguous()
    {
        $mockCommand = $this->createMock(Command::class);
        $mockCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('some.package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->willReturn(($mockCommands));

        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('key:controller:command'));
    }

    #[Test]
    public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound()
    {
        $this->expectException(NoSuchCommandException::class);
        $mockCommand = $this->createMock(Command::class);
        $mockCommand->expects($this->once())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        $this->commandManager->getCommandByIdentifier('package.key:controller:someothercommand');
    }

    #[Test]
    public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound()
    {
        $this->expectException(AmbiguousCommandIdentifierException::class);
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->once())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->once())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        $this->commandManager->getCommandByIdentifier('controller:command');
    }

    #[Test]
    public function getCommandByIdentifierThrowsExceptionIfOnlyPackageKeyIsSpecifiedAndContainsMoreThanOneCommand()
    {
        $this->expectException(AmbiguousCommandIdentifierException::class);
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->createMock(Command::class);
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand4 = $this->createMock(Command::class);
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        $this->commandManager->getCommandByIdentifier('package.key');
    }

    #[Test]
    public function getCommandsByIdentifierReturnsAnEmptyArrayIfNoCommandMatches()
    {
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller2:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        self::assertSame([], $this->commandManager->getCommandsByIdentifier('nonexistingpackage'));
    }

    #[Test]
    public function getCommandsByIdentifierReturnsAllCommandsOfTheSpecifiedPackage()
    {
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller2:command2'));
        $mockCommand3 = $this->createMock(Command::class);
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand4 = $this->createMock(Command::class);
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        $expectedResult = [$mockCommand3, $mockCommand4];
        self::assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('package.key'));
    }

    #[Test]
    public function getCommandsByIdentifierReturnsAllCommandsOfTheSpecifiedPackageIgnoringCase()
    {
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller2:command2'));
        $mockCommand3 = $this->createMock(Command::class);
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand4 = $this->createMock(Command::class);
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:othercommand'));
        $mockCommand5 = $this->createMock(Command::class);
        $mockCommand5->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('SomeOtherpackage.key:controller:othercommand'));
        $mockCommand6 = $this->createMock(Command::class);
        $mockCommand6->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('Some.Otherpackage.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4, $mockCommand5, $mockCommand6];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        $expectedResult = [$mockCommand1, $mockCommand2, $mockCommand6];
        self::assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('OtherPackage.Key'));
    }

    #[Test]
    public function getCommandsByIdentifierReturnsAllCommandsMatchingTheSpecifiedController()
    {
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller2:command2'));
        $mockCommand3 = $this->createMock(Command::class);
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand4 = $this->createMock(Command::class);
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:othercommand'));
        $mockCommand5 = $this->createMock(Command::class);
        $mockCommand5->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('some.otherpackage.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4, $mockCommand5];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->willReturn(($mockCommands));

        $expectedResult = [$mockCommand1, $mockCommand3, $mockCommand4, $mockCommand5];
        self::assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('controller'));
    }


    #[Test]
    public function getShortestIdentifierForCommandAlwaysReturnsShortNameForFlowHelpCommand()
    {
        $mockHelpCommand = $this->createMock(Command::class);
        $mockHelpCommand->expects($this->once())->method('getCommandIdentifier')->willReturn(('neos.flow:help:help'));
        $commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockHelpCommand);
        self::assertSame('help', $commandIdentifier);
    }

    #[Test]
    public function getShortestIdentifierForCommandReturnsTheCompleteIdentifiersForCustomHelpCommands()
    {
        $mockFlowHelpCommand = $this->createMock(Command::class);
        $mockFlowHelpCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('neos.flow:help:help'));
        $mockCustomHelpCommand = $this->createMock(Command::class);
        $mockCustomHelpCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('custom.package:help:help'));
        $mockCommands = [$mockFlowHelpCommand, $mockCustomHelpCommand];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->willReturn(($mockCommands));

        $commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockCustomHelpCommand);
        self::assertSame('package:help:help', $commandIdentifier);
    }

    #[Test]
    public function getShortestIdentifierForCommandReturnsShortestUnambiguousCommandIdentifiers()
    {
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('package.key:controller:command'));
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->createMock(Command::class);
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('packagekey:controller:command'));
        $mockCommand4 = $this->createMock(Command::class);
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn(('packagekey:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->willReturn(($mockCommands));

        self::assertSame('key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
        self::assertSame('controller2:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
        self::assertSame('packagekey:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand3));
        self::assertSame('controller:othercommand', $this->commandManager->getShortestIdentifierForCommand($mockCommand4));
    }

    #[Test]
    public function getShortestIdentifierForCommandReturnsCompleteCommandIdentifierForCommandsWithTheSameControllerAndCommandName()
    {
        $mockCommand1 = $this->createMock(Command::class);
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn('package.key:controller:command');
        $mockCommand2 = $this->createMock(Command::class);
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->willReturn('otherpackage.key:controller:command');
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->willReturn($mockCommands);

        self::assertSame('package.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
        self::assertSame('otherpackage.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
    }
}
