<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandManager;

/**
 * A Command Controller which provides help for available commands
 *
 * @Flow\Scope("singleton")
 */
class HelpCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var CommandManager
	 */
	protected $commandManager;

	/**
	 * @param \TYPO3\Flow\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\Flow\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function injectBootstrap(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
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
	 * This only outputs the Flow version number, context and some hint about how to
	 * get more help about commands.
	 *
	 * @return void
	 * @Flow\Internal
	 */
	public function helpStubCommand() {
		$context = $this->bootstrap->getContext();

		$this->outputLine('<b>TYPO3 Flow %s ("%s" context)</b>', array($this->packageManager->getPackage('TYPO3.Flow')->getPackageMetaData()->getVersion() ?: FLOW_VERSION_BRANCH, $context));
		$this->outputLine('<i>usage: %s <command identifier></i>', array($this->getFlowInvocationString()));
		$this->outputLine();
		$this->outputLine('See "%s help" for a list of all available commands.', array($this->getFlowInvocationString()));
		$this->outputLine();
	}

	/**
	 * Display help for a command
	 *
	 * The help command displays help for a given command:
	 * ./flow help <commandIdentifier>
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
			$matchingCommands = $this->commandManager->getCommandsByIdentifier($commandIdentifier);
			$numberOfMatchingCommands = count($matchingCommands);
			if ($numberOfMatchingCommands === 0) {
				$this->outputLine('No command could be found that matches the command identifier "%s".', array($commandIdentifier));
			} elseif ($numberOfMatchingCommands > 1) {
				$this->outputLine('%d commands match the command identifier "%s":', array($numberOfMatchingCommands, $commandIdentifier));
				$this->displayShortHelpForCommands($matchingCommands);
			} else {
				$this->displayHelpForCommand(array_shift($matchingCommands));
			}
		}
	}

	/**
	 * @return void
	 */
	protected function displayHelpIndex() {
		$context = $this->bootstrap->getContext();

		$this->outputLine('<b>TYPO3 Flow %s ("%s" context)</b>', array($this->packageManager->getPackage('TYPO3.Flow')->getPackageMetaData()->getVersion() ?: FLOW_VERSION_BRANCH, $context));
		$this->outputLine('<i>usage: %s <command identifier></i>', array($this->getFlowInvocationString()));
		$this->outputLine();
		$this->outputLine('The following commands are currently available:');

		$this->displayShortHelpForCommands($this->commandManager->getAvailableCommands());

		$this->outputLine('* = compile time command');
		$this->outputLine();
		$this->outputLine('See "%s help <commandidentifier>" for more information about a specific command.', array($this->getFlowInvocationString()));
		$this->outputLine();
	}

	/**
	 * @param array<\TYPO3\Flow\Cli\Command> $commands
	 * @return void
	 */
	protected function displayShortHelpForCommands(array $commands) {
		$commandsByPackagesAndControllers = $this->buildCommandsIndex($commands);
		foreach ($commandsByPackagesAndControllers as $packageKey => $commandControllers) {
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
	}

	/**
	 * Render help text for a single command
	 *
	 * @param \TYPO3\Flow\Cli\Command $command
	 * @return void
	 */
	protected function displayHelpForCommand(\TYPO3\Flow\Cli\Command $command) {
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
		$this->outputLine('  %s %s', array($this->getFlowInvocationString(), $usage));

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
				} catch (\TYPO3\Flow\Mvc\Exception\CommandException $exception) {
					$this->outputLine('%-2s%s (%s)', array(' ', $commandIdentifier, '<i>Command not available</i>'));
				}
			}
		}

		$this->outputLine();
	}

	/**
	 * Displays an error message
	 *
	 * @Flow\Internal
	 * @param \TYPO3\Flow\Mvc\Exception\CommandException $exception
	 * @return void
	 */
	public function errorCommand(\TYPO3\Flow\Mvc\Exception\CommandException $exception) {
		$this->outputLine($exception->getMessage());
		if ($exception instanceof \TYPO3\Flow\Mvc\Exception\AmbiguousCommandIdentifierException) {
			$this->outputLine('Please specify the complete command identifier. Matched commands:');
			$this->displayShortHelpForCommands($exception->getMatchingCommands());
		}
		$this->outputLine();
		$this->outputLine('Enter "%s help" for an overview of all available commands', array($this->getFlowInvocationString()));
		$this->outputLine('or "%s help <commandIdentifier>" for a detailed description of the corresponding command.', array($this->getFlowInvocationString()));
	}

	/**
	 * Builds an index of available commands. For each of them a Command object is
	 * added to the commands array of this class.
	 *
	 * @param array<\TYPO3\Flow\Cli\Command> $commands
	 * @return array in the format array('<packageKey>' => array('<CommandControllerClassName>', array('<command1>' => $command1, '<command2>' => $command2)))
	 */
	protected function buildCommandsIndex(array $commands) {
		$commandsByPackagesAndControllers = array();
		foreach ($commands as $command) {
			if ($command->isInternal()) {
				continue;
			}
			$commandIdentifier = $command->getCommandIdentifier();
			$packageKey = strstr($commandIdentifier, ':', TRUE);
			$commandControllerClassName = $command->getControllerClassName();
			$commandName = $command->getControllerCommandName();
			$commandsByPackagesAndControllers[$packageKey][$commandControllerClassName][$commandName] = $command;
		}
		return $commandsByPackagesAndControllers;
	}
}
