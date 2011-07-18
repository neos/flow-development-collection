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
	 * ./flow3 help
	 *
	 * @return string
	 */
	public function helpCommand() {
		$this->buildCommandsIndex();

		$context = $this->bootstrap->getContext();

		$this->response->appendContent('FLOW3 ' . $this->packageManager->getPackage('TYPO3.FLOW3')->getPackageMetaData()->getVersion() . ' (' . $context . ')');
		$this->response->appendContent('usage: ./flow3 <command identifier>');
		$this->response->appendContent('');
		$this->response->appendContent('The following commands are currently available:');

		foreach ($this->commandsByPackagesAndControllers as $packageKey => $commandControllers) {
			$this->response->appendContent('');
			$this->response->appendContent(sprintf('PACKAGE "%s":', strtoupper($packageKey)));
			foreach ($commandControllers as $commands) {
				$this->response->appendContent('');
				foreach ($commands as $command) {
					$commandIdentifier = $command->getCommandIdentifier();
					$compileTimeSymbol = ($this->bootstrap->isCompileTimeCommand($commandIdentifier) ? '⚒ ' : '  ');
					$this->response->appendContent('  ' . $compileTimeSymbol . str_pad($commandIdentifier, 50) . '  ' . $command->getShortDescription());
				}
			}
		}

		$this->response->appendContent("\n⚒ compile time command\n");
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
		$this->response->appendContent($exception->getMessage());
		if ($exception instanceof \TYPO3\FLOW3\MVC\Exception\AmbiguousCommandIdentifierException) {
			$this->response->appendContent('Please specify the complete command identifier. Matched commands:');
			foreach ($exception->getMatchingCommands() as $matchingCommand) {
				$this->response->appendContent('    ' . $matchingCommand->getCommandIdentifier());
			}
		}
		$this->response->appendContent('');
		$this->response->appendContent('Enter ./flow3 for help');
	}

	/**
	 * Builds an index of available commands. For each of them a Command object is added to the commands array of this
	 * class.
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