<?php
namespace TYPO3\FLOW3\Command;

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

use \TYPO3\FLOW3\MVC\CLI\Command;
use \TYPO3\FLOW3\MVC\CLI\CommandManager;

/**
 * A Command Controller which provides help for available commands
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class HelpCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var CommandManager
	 */
	protected $commandManager;

	/**
	 * @var array
	 */
	protected $commandsByPackagesAndControllers = array();

	/**
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function injectBootstrap(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * @param CommandManager $commandManager
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectCommandManager(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Display help for a command
	 *
	 * The help command displays help for a given command:
	 * ./flow3 help <commandIdentifier>
	 *
	 * @param string $commandIdentifier Identifier of a command for more details
	 * @return string
	 */
	public function helpCommand($commandIdentifier = NULL) {
		if ($commandIdentifier === NULL) {
			$this->displayHelpIndex();
		} else {
			try {
				$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
			} catch (\TYPO3\FLOW3\MVC\Exception\CommandException $exception) {
				$this->outputLine($exception->getMessage());
				return;
			}
			$this->displayHelpForCommand($command);
		}
	}

	/**
	 * @return void
	 */
	protected function displayHelpIndex() {
		$this->buildCommandsIndex();
		$context = $this->bootstrap->getContext();

		$this->outputLine('FLOW3 %s (%s)', array($this->packageManager->getPackage('TYPO3.FLOW3')->getPackageMetaData()->getVersion(), $context));
		$this->outputLine('usage: ./flow3 <command identifier>');
		$this->outputLine('');
		$this->outputLine('The following commands are currently available:');

		foreach ($this->commandsByPackagesAndControllers as $packageKey => $commandControllers) {
			$this->outputLine('');
			$this->outputLine('PACKAGE "%s":', array(strtoupper($packageKey)));
			$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			foreach ($commandControllers as $commands) {
				foreach ($commands as $command) {
					$description = wordwrap($command->getShortDescription(), self::MAXIMUM_LINE_LENGTH - 43, PHP_EOL . str_repeat(' ', 43), TRUE);
					$shortCommandIdentifier = $this->commandManager->getShortestIdentifierForCommand($command);
					$compileTimeSymbol = ($this->bootstrap->isCompileTimeCommand($shortCommandIdentifier) ? '*' : '');
					$this->outputLine('%-2s%-40s %s', array($compileTimeSymbol, $shortCommandIdentifier , $description));
				}
				$this->outputLine();
			}
		}
		$this->outputLine('* = compile time command');
		$this->outputLine();
		$this->outputLine('See \'./flow3 help <commandidentifier>\' for more information about a specific command.');
		$this->outputLine();
	}

	/**
	 * Render help text for a single command
	 *
	 * @param \TYPO3\FLOW3\MVC\CLI\Command $command
	 * @return void
	 */
	protected function displayHelpForCommand(\TYPO3\FLOW3\MVC\CLI\Command $command) {
		$this->outputLine($command->getShortDescription());
		$commandArgumentDefinitions = $command->getArgumentDefinitions();
		$usage = './flow3 ' . $this->commandManager->getShortestIdentifierForCommand($command);
		foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
			$argumentName = sprintf('<%s>', $commandArgumentDefinition->getName());
			if (!$commandArgumentDefinition->isRequired()) {
				$argumentName = sprintf('[%s]', $argumentName);
			}
			$usage .= ' ' . $argumentName;
		}
		$this->outputLine();
		$this->outputLine('USAGE:');
		$this->outputLine($usage);
		if ($command->hasArguments()) {
			$this->outputLine();
			$this->outputLine('ARGUMENTS:');
			foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
				$argumentDescription = $commandArgumentDefinition->getDescription();
				if (!$commandArgumentDefinition->isRequired()) {
					$argumentDescription .= ' (optional)';
				}
				$argumentDescription = wordwrap($argumentDescription, self::MAXIMUM_LINE_LENGTH - 23, PHP_EOL . str_repeat(' ', 23), TRUE);
				$this->outputLine('  %-20s %s', array($commandArgumentDefinition->getName(), $argumentDescription));
			}
		}
	}

	/**
	 * Displays an error message
	 *
	 * @internal
	 * @param \TYPO3\FLOW3\MVC\Exception\CommandException $exception
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function errorCommand(\TYPO3\FLOW3\MVC\Exception\CommandException $exception) {
		$this->outputLine($exception->getMessage());
		if ($exception instanceof \TYPO3\FLOW3\MVC\Exception\AmbiguousCommandIdentifierException) {
			$this->outputLine('Please specify the complete command identifier. Matched commands:');
			foreach ($exception->getMatchingCommands() as $matchingCommand) {
				$this->outputLine('    %s', array($matchingCommand->getCommandIdentifier()));
			}
		}
		$this->outputLine('');
		$this->outputLine('Enter "./flow3 help" for an overview of all available commands');
		$this->outputLine('or "./flow3 help <commandIdentifier>" for a detailed description of the corresponding command.');
	}

	/**
	 * Builds an index of available commands. For each of them a Command object is
	 * added to the commands array of this class.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function buildCommandsIndex() {
		$availableCommands = $this->commandManager->getAvailableCommands();
		foreach ($availableCommands as $command) {
			if ($command->isInternal()) {
				continue;
			}
			$commandIdentifier = $command->getCommandIdentifier();
			$packageKey = strstr($commandIdentifier, ':', TRUE);
			$commandControllerClassName = $command->getControllerClassName();
			$commandName = $command->getControllerCommandName();
			$this->commandsByPackagesAndControllers[$packageKey][$commandControllerClassName][$commandName] = $command;
		}
	}
}
?>
