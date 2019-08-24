<?php
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

use Neos\Flow\Cli\CommandManager;
use Neos\Flow\Cli;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Exception\AmbiguousCommandIdentifierException;
use Neos\Flow\Mvc\Exception\NoSuchCommandException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;

require_once('Fixtures/Command/MockCommandController.php');

/**
 * Testcase for the CLI CommandManager class
 */
class CommandManagerTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    /**
     * @var Bootstrap
     */
    protected $mockBootstrap;

    /**
     * @var Cli\CommandManager
     */
    protected $commandManager;

    protected function setUp(): void
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->commandManager = $this->getMockBuilder(Cli\CommandManager::class)->setMethods(['getAvailableCommands'])->getMock();

        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->commandManager->injectBootstrap($this->mockBootstrap);
    }

    /**
     * @test
     */
    public function getAvailableCommandsReturnsAllAvailableCommands()
    {
        $commandManager = new CommandManager();
        $mockCommandControllerClassNames = [Fixtures\Command\MockACommandController::class, Fixtures\Command\MockBCommandController::class];
        $this->mockReflectionService->expects(self::once())->method('getAllSubClassNamesForClass')->with(Cli\CommandController::class)->will(self::returnValue($mockCommandControllerClassNames));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $commandManager->injectObjectManager($mockObjectManager);

        $commands = $commandManager->getAvailableCommands();
        self::assertEquals(3, count($commands));
        self::assertEquals('neos.flow.tests.unit.cli.fixtures:mocka:foo', $commands[0]->getCommandIdentifier());
        self::assertEquals('neos.flow.tests.unit.cli.fixtures:mocka:bar', $commands[1]->getCommandIdentifier());
        self::assertEquals('neos.flow.tests.unit.cli.fixtures:mockb:baz', $commands[2]->getCommandIdentifier());
    }

    /**
     * @test
     */
    public function getCommandByIdentifierReturnsCommandIfIdentifierIsEqual()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects(self::once())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
    }

    /**
     * @test
     */
    public function getCommandByIdentifierWorksCaseInsensitive()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects(self::once())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   Package.Key:conTroLler:Command  '));
    }

    /**
     * @test
     */
    public function getCommandByIdentifierAllowsThePackageKeyToOnlyContainTheLastPartOfThePackageNamespaceIfCommandsAreUnambiguous()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('some.package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects(self::atLeastOnce())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
        self::assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('key:controller:command'));
    }

    /**
     * @test
     */
    public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound()
    {
        $this->expectException(NoSuchCommandException::class);
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects(self::once())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $this->commandManager->getCommandByIdentifier('package.key:controller:someothercommand');
    }

    /**
     * @test
     */
    public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound()
    {
        $this->expectException(AmbiguousCommandIdentifierException::class);
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::once())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::once())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $this->commandManager->getCommandByIdentifier('controller:command');
    }

    /**
     * @test
     */
    public function getCommandByIdentifierThrowsExceptionIfOnlyPackageKeyIsSpecifiedAndContainsMoreThanOneCommand()
    {
        $this->expectException(AmbiguousCommandIdentifierException::class);
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $this->commandManager->getCommandByIdentifier('package.key');
    }

    /**
     * @test
     */
    public function getCommandsByIdentifierReturnsAnEmptyArrayIfNoCommandMatches()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller2:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        self::assertSame([], $this->commandManager->getCommandsByIdentifier('nonexistingpackage'));
    }

    /**
     * @test
     */
    public function getCommandsByIdentifierReturnsAllCommandsOfTheSpecifiedPackage()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller2:command2'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $expectedResult = [$mockCommand3, $mockCommand4];
        self::assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('package.key'));
    }

    /**
     * @test
     */
    public function getCommandsByIdentifierReturnsAllCommandsOfTheSpecifiedPackageIgnoringCase()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller2:command2'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:othercommand'));
        $mockCommand5 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand5->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('SomeOtherpackage.key:controller:othercommand'));
        $mockCommand6 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand6->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('Some.Otherpackage.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4, $mockCommand5, $mockCommand6];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $expectedResult = [$mockCommand1, $mockCommand2, $mockCommand6];
        self::assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('OtherPackage.Key'));
    }

    /**
     * @test
     */
    public function getCommandsByIdentifierReturnsAllCommandsMatchingTheSpecifiedController()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller2:command2'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:othercommand'));
        $mockCommand5 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand5->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('some.otherpackage.key:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4, $mockCommand5];
        $this->commandManager->expects(self::once())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $expectedResult = [$mockCommand1, $mockCommand3, $mockCommand4, $mockCommand5];
        self::assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('controller'));
    }


    /**
     * @test
     */
    public function getShortestIdentifierForCommandAlwaysReturnsShortNameForFlowHelpCommand()
    {
        $mockHelpCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockHelpCommand->expects(self::once())->method('getCommandIdentifier')->will(self::returnValue('neos.flow:help:help'));
        $commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockHelpCommand);
        self::assertSame('help', $commandIdentifier);
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandReturnsTheCompleteIdentifiersForCustomHelpCommands()
    {
        $mockFlowHelpCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockFlowHelpCommand->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('neos.flow:help:help'));
        $mockCustomHelpCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCustomHelpCommand->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('custom.package:help:help'));
        $mockCommands = [$mockFlowHelpCommand, $mockCustomHelpCommand];
        $this->commandManager->expects(self::atLeastOnce())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        $commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockCustomHelpCommand);
        self::assertSame('package:help:help', $commandIdentifier);
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandReturnsShortestUnambiguousCommandIdentifiers()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('packagekey:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects(self::atLeastOnce())->method('getCommandIdentifier')->will(self::returnValue('packagekey:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects(self::atLeastOnce())->method('getAvailableCommands')->will(self::returnValue($mockCommands));

        self::assertSame('key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
        self::assertSame('controller2:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
        self::assertSame('packagekey:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand3));
        self::assertSame('controller:othercommand', $this->commandManager->getShortestIdentifierForCommand($mockCommand4));
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandReturnsCompleteCommandIdentifierForCommandsWithTheSameControllerAndCommandName()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects(self::atLeastOnce())->method('getCommandIdentifier')->willReturn('package.key:controller:command');
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects(self::atLeastOnce())->method('getCommandIdentifier')->willReturn('otherpackage.key:controller:command');
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects(self::atLeastOnce())->method('getAvailableCommands')->willReturn($mockCommands);

        self::assertSame('package.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
        self::assertSame('otherpackage.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
    }
}
