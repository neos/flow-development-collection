<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\CLI;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\MVC\CLI\CommandManager;

require_once(__DIR__ . '/../Fixture/CLI/Command/MockCommandController.php');

/**
 * Testcase for the CLI CommandManager class
 */
class CommandManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var CommandManager
	 */
	protected $commandManager;

	public function setUp() {
		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$this->commandManager = $this->getMock('TYPO3\FLOW3\MVC\CLI\CommandManager', array('getAvailableCommands'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getAvailableCommandsReturnsAllAvailableCommands() {
		$commandManager = new CommandManager();
		$commandManager->injectReflectionService($this->mockReflectionService);
		$mockCommandControllerClassNames = array('TYPO3\FLOW3\MVC\Fixture\CLI\Command\MockACommandController', 'TYPO3\FLOW3\MVC\Fixture\CLI\Command\MockBCommandController');
		$this->mockReflectionService->expects($this->once())->method('getAllSubClassNamesForClass')->with('TYPO3\FLOW3\MVC\Controller\CommandController')->will($this->returnValue($mockCommandControllerClassNames));

		$commands = $commandManager->getAvailableCommands();
		$this->assertEquals(3, count($commands));
		$this->assertEquals('typo3.flow3.mvc.fixture.cli:mocka:foo', $commands[0]->getCommandIdentifier());
		$this->assertEquals('typo3.flow3.mvc.fixture.cli:mocka:bar', $commands[1]->getCommandIdentifier());
		$this->assertEquals('typo3.flow3.mvc.fixture.cli:mockb:baz', $commands[2]->getCommandIdentifier());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierReturnsCommandIfIdentifierIsEqual() {
		$mockCommand = $this->getMock('TYPO3\FLOW3\MVC\CLI\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierWorksCaseInsensitive() {
		$mockCommand = $this->getMock('TYPO3\FLOW3\MVC\CLI\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   Package.Key:conTroLler:Command  '));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierAllowsThePackageKeyToOnlyContainTheLastPartOfThePackageNamespaceIfCommandsAreUnambiguous() {
		$mockCommand = $this->getMock('TYPO3\FLOW3\MVC\CLI\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->atLeastOnce())->method('getCommandIdentifier')->will($this->returnValue('some.package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->atLeastOnce())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('package.key:controller:command'));
		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('key:controller:command'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\NoSuchCommandException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound() {
		$mockCommand = $this->getMock('TYPO3\FLOW3\MVC\CLI\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('package.key:controller:someothercommand');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception\AmbiguousCommandIdentifierException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound() {
		$mockCommand1 = $this->getMock('TYPO3\FLOW3\MVC\CLI\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('package.key:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\FLOW3\MVC\CLI\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('otherpackage.key:controller:command'));
		$mockCommands = array($mockCommand1, $mockCommand2);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('controller:command');
	}

}
?>