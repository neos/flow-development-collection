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

    public function setUp()
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
        $mockCommandControllerClassNames = array(Fixtures\Command\MockACommandController::class, Fixtures\Command\MockBCommandController::class);
        $this->mockReflectionService->expects($this->once())->method('getAllSubClassNamesForClass')->with(Cli\CommandController::class)->will($this->returnValue($mockCommandControllerClassNames));
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $commandManager->injectObjectManager($mockObjectManager);

        $commands = $commandManager->getAvailableCommands();
        $this->assertEquals(3, count($commands));
        $this->assertEquals('neos.flow.tests.unit.cli.fixtures:mocka:foo', $commands[0]->getCommandIdentifier());
        $this->assertEquals('neos.flow.tests.unit.cli.fixtures:mocka:bar', $commands[1]->getCommandIdentifier());
        $this->assertEquals('neos.flow.tests.unit.cli.fixtures:mockb:baz', $commands[2]->getCommandIdentifier());
    }

    /**
     * @test
     */
    public function getCommandByIdentifierReturnsCommandIfIdentifierIsEqual()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
    }

    /**
     * @test
     */
    public function getCommandByIdentifierWorksCaseInsensitive()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   Package.Key:conTroLler:Command  '));
    }

    /**
     * @test
     */
    public function getCommandByIdentifierAllowsThePackageKeyToOnlyContainTheLastPartOfThePackageNamespaceIfCommandsAreUnambiguous()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('some.package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
        $this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('key:controller:command'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\NoSuchCommandException
     */
    public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound()
    {
        $mockCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommands = [$mockCommand];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->commandManager->getCommandByIdentifier('package.key:controller:someothercommand');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\AmbiguousCommandIdentifierException
     */
    public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->commandManager->getCommandByIdentifier('controller:command');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\AmbiguousCommandIdentifierException
     */
    public function getCommandByIdentifierThrowsExceptionIfOnlyPackageKeyIsSpecifiedAndContainsMoreThanOneCommand()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->commandManager->getCommandByIdentifier('packagekey');
    }

    /**
     * @test
     */
    public function getCommandsByIdentifierReturnsAnEmptyArrayIfNoCommandMatches()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->assertSame([], $this->commandManager->getCommandsByIdentifier('nonexistingpackage'));
    }

    /**
     * @test
     */
    public function getCommandsByIdentifierReturnsAllCommandsOfTheSpecifiedPackage()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $expectedResult = [$mockCommand3, $mockCommand4];
        $this->assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('packagekey'));
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandAlwaysReturnsShortNameForFlowHelpCommand()
    {
        $mockHelpCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockHelpCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('neos.flow:help:help'));
        $commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockHelpCommand);
        $this->assertSame('help', $commandIdentifier);
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandReturnsTheCompleteIdentifiersForCustomHelpCommands()
    {
        $mockFlowHelpCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockFlowHelpCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('neos.flow:help:help'));
        $mockCustomHelpCommand = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCustomHelpCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('custom.package:help:help'));
        $mockCommands = [$mockFlowHelpCommand, $mockCustomHelpCommand];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockCustomHelpCommand);
        $this->assertSame('package:help:help', $commandIdentifier);
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandReturnsShortestUnambiguousCommandIdentifiers()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
        $mockCommand3 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:command'));
        $mockCommand4 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:othercommand'));
        $mockCommands = [$mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->assertSame('key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
        $this->assertSame('controller2:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
        $this->assertSame('packagekey:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand3));
        $this->assertSame('controller:othercommand', $this->commandManager->getShortestIdentifierForCommand($mockCommand4));
    }

    /**
     * @test
     */
    public function getShortestIdentifierForCommandReturnsCompleteCommandIdentifierForCommandsWithTheSameControllerAndCommandName()
    {
        $mockCommand1 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
        $mockCommand2 = $this->getMockBuilder(Cli\Command::class)->disableOriginalConstructor()->getMock();
        $mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller:command'));
        $mockCommands = [$mockCommand1, $mockCommand2];
        $this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

        $this->assertSame('package.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
        $this->assertSame('otherpackage.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
    }
}
