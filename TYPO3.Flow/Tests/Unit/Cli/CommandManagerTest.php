<?php
namespace TYPO3\Flow\Tests\Unit\Cli;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cli\CommandManager;

require_once('Fixtures/Command/MockCommandController.php');

/**
 * Testcase for the CLI CommandManager class
 */
class CommandManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $mockBootstrap;

	/**
	 * @var \TYPO3\Flow\Cli\CommandManager
	 */
	protected $commandManager;

	public function setUp() {
		$this->mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$this->commandManager = $this->getMock('TYPO3\Flow\Cli\CommandManager', array('getAvailableCommands'));

		$this->mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array(), array(), '', FALSE);
		$this->commandManager->injectBootstrap($this->mockBootstrap);
	}

	/**
	 * @test
	 */
	public function getAvailableCommandsReturnsAllAvailableCommands() {
		$commandManager = new CommandManager();
		$commandManager->injectReflectionService($this->mockReflectionService);
		$mockCommandControllerClassNames = array('TYPO3\Flow\Tests\Unit\Cli\Fixtures\Command\MockACommandController', 'TYPO3\Flow\Tests\Unit\Cli\Fixtures\Command\MockBCommandController');
		$this->mockReflectionService->expects($this->once())->method('getAllSubClassNamesForClass')->with('TYPO3\Flow\Cli\CommandController')->will($this->returnValue($mockCommandControllerClassNames));

		$commands = $commandManager->getAvailableCommands();
		$this->assertEquals(3, count($commands));
		$this->assertEquals('typo3.flow.tests.unit.cli.fixtures:mocka:foo', $commands[0]->getCommandIdentifier());
		$this->assertEquals('typo3.flow.tests.unit.cli.fixtures:mocka:bar', $commands[1]->getCommandIdentifier());
		$this->assertEquals('typo3.flow.tests.unit.cli.fixtures:mockb:baz', $commands[2]->getCommandIdentifier());
	}

	/**
	 * @test
	 */
	public function getCommandByIdentifierReturnsCommandIfIdentifierIsEqual() {
		$mockCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
	}

	/**
	 * @test
	 */
	public function getCommandByIdentifierWorksCaseInsensitive() {
		$mockCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   Package.Key:conTroLler:Command  '));
	}

	/**
	 * @test
	 */
	public function getCommandByIdentifierAllowsThePackageKeyToOnlyContainTheLastPartOfThePackageNamespaceIfCommandsAreUnambiguous() {
		$mockCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('some.package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('key:controller:command'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\NoSuchCommandException
	 */
	public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound() {
		$mockCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('package.key:controller:someothercommand');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\AmbiguousCommandIdentifierException
	 */
	public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound() {
		$mockCommand1 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller:command'));
		$mockCommands = array($mockCommand1, $mockCommand2);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('controller:command');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\AmbiguousCommandIdentifierException
	 */
	public function getCommandByIdentifierThrowsExceptionIfOnlyPackageKeyIsSpecifiedAndContainsMoreThanOneCommand() {
		$mockCommand1 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
		$mockCommand3 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:command'));
		$mockCommand4 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:othercommand'));
		$mockCommands = array($mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('packagekey');
	}

	/**
	 * @test
	 */
	public function getCommandsByIdentifierReturnsAnEmptyArrayIfNoCommandMatches() {
		$mockCommand1 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
		$mockCommands = array($mockCommand1, $mockCommand2);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame(array(), $this->commandManager->getCommandsByIdentifier('nonexistingpackage'));
	}

	/**
	 * @test
	 */
	public function getCommandsByIdentifierReturnsAllCommandsOfTheSpecifiedPackage() {
		$mockCommand1 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
		$mockCommand3 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:command'));
		$mockCommand4 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:othercommand'));
		$mockCommands = array($mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$expectedResult = array($mockCommand3, $mockCommand4);
		$this->assertSame($expectedResult, $this->commandManager->getCommandsByIdentifier('packagekey'));
	}

	/**
	 * @test
	 */
	public function getShortestIdentifierForCommandAlwaysReturnsShortNameForFlowHelpCommand() {
		$mockHelpCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockHelpCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('typo3.flow:help:help'));
		$commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockHelpCommand);
		$this->assertSame('help', $commandIdentifier);
	}

	/**
	 * @test
	 */
	public function getShortestIdentifierForCommandReturnsTheCompleteIdentifiersForCustomHelpCommands() {
		$mockFlowHelpCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockFlowHelpCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('typo3.flow:help:help'));
		$mockCustomHelpCommand = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCustomHelpCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('custom.package:help:help'));
		$mockCommands = array($mockFlowHelpCommand, $mockCustomHelpCommand);
		$this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$commandIdentifier = $this->commandManager->getShortestIdentifierForCommand($mockCustomHelpCommand);
		$this->assertSame('package:help:help', $commandIdentifier);
	}

	/**
	 * @test
	 */
	public function getShortestIdentifierForCommandReturnsShortestUnambiguousCommandIdentifiers() {
		$mockCommand1 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller2:command'));
		$mockCommand3 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand3->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:command'));
		$mockCommand4 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand4->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('packagekey:controller:othercommand'));
		$mockCommands = array($mockCommand1, $mockCommand2, $mockCommand3, $mockCommand4);
		$this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame('key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
		$this->assertSame('controller2:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
		$this->assertSame('packagekey:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand3));
		$this->assertSame('controller:othercommand', $this->commandManager->getShortestIdentifierForCommand($mockCommand4));
	}

	/**
	 * @test
	 */
	public function getShortestIdentifierForCommandReturnsCompleteCommandIdentifierForCommandsWithTheSameControllerAndCommandName() {
		$mockCommand1 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\Flow\Cli\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller:command'));
		$mockCommands = array($mockCommand1, $mockCommand2);
		$this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame('package.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand1));
		$this->assertSame('otherpackage.key:controller:command', $this->commandManager->getShortestIdentifierForCommand($mockCommand2));
	}

}
