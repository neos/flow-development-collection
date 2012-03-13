<?php
namespace TYPO3\FLOW3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

use \TYPO3\FLOW3\MVC\CLI\Command;
use \TYPO3\FLOW3\MVC\CLI\CommandManager;

/**
 * A Command Controller which provides help for available commands
 *
 * @FLOW3\Scope("singleton")
 */
class HelpCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

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
	 */
	public function injectCommandManager(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Displays a short, general help message
	 *
	 * This only outputs the FLOW3 version number, context and some hint about how to
	 * get more help about commands.
	 *
	 * @return void
	 * @FLOW3\Internal
	 */
	public function helpStubCommand() {
		$context = $this->bootstrap->getContext();

		$this->outputLine('<b>FLOW3 %s ("%s" context)</b>', array($this->packageManager->getPackage('TYPO3.FLOW3')->getPackageMetaData()->getVersion(), $context));
		$this->outputLine('<i>usage: %s <command identifier></i>', array($this->getFlow3InvocationString()));
		$this->outputLine();
		$this->outputLine('See "%s help" for a list of all available commands.', array($this->getFlow3InvocationString()));
		$this->outputLine();
	}

	/**
	 * Display help for a command
	 *
	 * The help command displays help for a given command:
	 * ./flow3 help <commandIdentifier>
	 *
	 * @param string $commandIdentifier Identifier of a command for more details
	 * @return void
	 */
	public function helpCommand($commandIdentifier = NULL) {
		$exceedingArguments = $this->request->getExceedingArguments();
		if (count($exceedingArguments) > 0 && $commandIdentifier === NULL) {
			$commandIdentifier = $exceedingArguments[0];
		}

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

		$this->outputLine('<b>FLOW3 %s ("%s" context)</b>', array($this->packageManager->getPackage('TYPO3.FLOW3')->getPackageMetaData()->getVersion(), $context));
		$this->outputLine('<i>usage: %s <command identifier></i>', array($this->getFlow3InvocationString()));
		$this->outputLine();
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
		$this->outputLine('See "%s help <commandidentifier>" for more information about a specific command.', array($this->getFlow3InvocationString()));
		$this->outputLine();
	}

	/**
	 * Render help text for a single command
	 *
	 * @param \TYPO3\FLOW3\MVC\CLI\Command $command
	 * @return void
	 */
	protected function displayHelpForCommand(\TYPO3\FLOW3\MVC\CLI\Command $command) {
		$this->outputLine();
		$this->outputLine('<u>' . $command->getShortDescription() . '</u>');
		$this->outputLine();

		$this->outputLine('<b>COMMAND:</b>');
		$name = '<i>' . $command->getCommandIdentifier() . '</i>';
		$this->outputLine('%-2s%s', array(' ', $name));

		$commandArgumentDefinitions = $command->getArgumentDefinitions();
		$usage = '';
		$hasOptions = FALSE;
		foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
			if (!$commandArgumentDefinition->isRequired()) {
				$hasOptions = TRUE;
			} else {
				$usage .= sprintf(' <%s>', strtolower(preg_replace('/([A-Z])/', ' $1', $commandArgumentDefinition->getName())));
			}
		}

		$usage = $this->commandManager->getShortestIdentifierForCommand($command) . ($hasOptions ? ' [<options>]' : '') . $usage;

		$this->outputLine();
		$this->outputLine('<b>USAGE:</b>');
		$this->outputLine('  %s %s', array($this->getFlow3InvocationString(), $usage));

		$argumentDescriptions = array();
		$optionDescriptions = array();

		if ($command->hasArguments()) {
			foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
				$argumentDescription = $commandArgumentDefinition->getDescription();
				$argumentDescription = wordwrap($argumentDescription, self::MAXIMUM_LINE_LENGTH - 23, PHP_EOL . str_repeat(' ', 23), TRUE);
				if ($commandArgumentDefinition->isRequired()) {
					$argumentDescriptions[] = vsprintf('  %-20s %s', array($commandArgumentDefinition->getDashedName(), $argumentDescription));
				} else {
					$optionDescriptions[] = vsprintf('  %-20s %s', array($commandArgumentDefinition->getDashedName(), $argumentDescription));
				}
			}
		}

		if (count($argumentDescriptions) > 0) {
			$this->outputLine();
			$this->outputLine('<b>ARGUMENTS:</b>');
			foreach ($argumentDescriptions as $argumentDescription) {
				$this->outputLine($argumentDescription);
			}
		}

		if (count($optionDescriptions) > 0) {
			$this->outputLine();
			$this->outputLine('<b>OPTIONS:</b>');
			foreach ($optionDescriptions as $optionDescription) {
				$this->outputLine($optionDescription);
			}
		}

		if ($command->getDescription() !== '') {
			$this->outputLine();
			$this->outputLine('<b>DESCRIPTION:</b>');
			$descriptionLines = explode(chr(10), $command->getDescription());
			foreach ($descriptionLines as $descriptionLine) {
				$this->outputLine('%-2s%s', array(' ', $descriptionLine));
			}
		}

		$relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
		if ($relatedCommandIdentifiers !== array()) {
			$this->outputLine();
			$this->outputLine('<b>SEE ALSO:</b>');
			foreach ($relatedCommandIdentifiers as $commandIdentifier) {
				try {
					$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
					$this->outputLine('%-2s%s (%s)', array(' ', $commandIdentifier, $command->getShortDescription()));
				} catch (\TYPO3\FLOW3\MVC\Exception\CommandException $exception) {
					$this->outputLine('%-2s%s (%s)', array(' ', $commandIdentifier, '<i>Command not available</i>'));
				}
			}
		}

		$this->outputLine();
	}

	/**
	 * Displays an error message
	 *
	 * @FLOW3\Internal
	 * @param \TYPO3\FLOW3\MVC\Exception\CommandException $exception
	 * @return void
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
		$this->outputLine('Enter "%s help" for an overview of all available commands', array($this->getFlow3InvocationString()));
		$this->outputLine('or "%s help <commandIdentifier>" for a detailed description of the corresponding command.', array($this->getFlow3InvocationString()));
	}

	/**
	 * Builds an index of available commands. For each of them a Command object is
	 * added to the commands array of this class.
	 *
	 * @return void
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
