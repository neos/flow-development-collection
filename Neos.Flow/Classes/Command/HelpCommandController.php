<?php
namespace Neos\Flow\Command;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\Command;
use Neos\Flow\Cli\CommandArgumentDefinition;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Exception\AmbiguousCommandIdentifierException;
use Neos\Flow\Mvc\Exception\CommandException;
use Neos\Flow\Package\PackageManagerInterface;

/**
 * A Command Controller which provides help for available commands
 *
 * @Flow\Scope("singleton")
 */
class HelpCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\InjectConfiguration(path = "core.applicationPackageKey")
     * @var string
     */
    protected $applicationPackageKey;

    /**
     * @Flow\InjectConfiguration(path = "core.applicationName")
     * @var string
     */
    protected $applicationName;

    /**
     * Displays a short, general help message
     *
     * This only outputs the Flow version number, context and some hint about how to
     * get more help about commands.
     *
     * @return void
     * @Flow\Internal
     */
    public function helpStubCommand()
    {
        $context = $this->bootstrap->getContext();
        $applicationPackage = $this->packageManager->getPackage($this->applicationPackageKey);
        $this->outputLine('<b>%s %s ("%s" context)</b>', [$this->applicationName, $applicationPackage->getInstalledVersion() ?: 'dev', $context]);
        $this->outputLine('<i>usage: %s <command identifier></i>', array($this->getFlowInvocationString()));
        $this->outputLine();
        $this->outputLine('See "%s help" for a list of all available commands.', [$this->getFlowInvocationString()]);
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
    public function helpCommand($commandIdentifier = null)
    {
        $exceedingArguments = $this->request->getExceedingArguments();
        if (count($exceedingArguments) > 0 && $commandIdentifier === null) {
            $commandIdentifier = $exceedingArguments[0];
        }

        if ($commandIdentifier === null) {
            $this->displayHelpIndex();
        } else {
            $matchingCommands = $this->commandManager->getCommandsByIdentifier($commandIdentifier);
            $numberOfMatchingCommands = count($matchingCommands);
            if ($numberOfMatchingCommands === 0) {
                $this->outputLine('No command could be found that matches the command identifier "%s".', [$commandIdentifier]);
            } elseif ($numberOfMatchingCommands > 1) {
                $this->outputLine('%d commands match the command identifier "%s":', [$numberOfMatchingCommands, $commandIdentifier]);
                $this->displayShortHelpForCommands($matchingCommands);
            } else {
                $this->displayHelpForCommand(array_shift($matchingCommands));
            }
        }
    }

    /**
     * @return void
     */
    protected function displayHelpIndex()
    {
        $context = $this->bootstrap->getContext();

        $applicationPackage = $this->packageManager->getPackage($this->applicationPackageKey);
        $this->outputLine('<b>%s %s ("%s" context)</b>', [$applicationPackage->getComposerManifest('description'), $applicationPackage->getInstalledVersion() ?: 'dev', $context]);
        $this->outputLine('<i>usage: %s <command identifier></i>', [$this->getFlowInvocationString()]);
        $this->outputLine();
        $this->outputLine('The following commands are currently available:');

        $this->displayShortHelpForCommands($this->commandManager->getAvailableCommands());

        $this->outputLine('* = compile time command');
        $this->outputLine();
        $this->outputLine('See "%s help <commandidentifier>" for more information about a specific command.', [$this->getFlowInvocationString()]);
        $this->outputLine();
    }

    /**
     * @param array<Command> $commands
     * @return void
     */
    protected function displayShortHelpForCommands(array $commands)
    {
        $commandsByPackagesAndControllers = $this->buildCommandsIndex($commands);
        foreach ($commandsByPackagesAndControllers as $packageKey => $commandControllers) {
            $this->outputLine('');
            $this->outputLine('PACKAGE "%s":', [strtoupper($packageKey)]);
            $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));
            foreach ($commandControllers as $commands) {
                /** @var Command $command */
                foreach ($commands as $command) {
                    $description = wordwrap($command->getShortDescription(), $this->output->getMaximumLineLength() - 43, PHP_EOL . str_repeat(' ', 43), true);
                    $shortCommandIdentifier = $this->commandManager->getShortestIdentifierForCommand($command);
                    $compileTimeSymbol = ($this->bootstrap->isCompileTimeCommand($shortCommandIdentifier) ? '*' : '');
                    $this->outputLine('%-2s%-40s %s', [$compileTimeSymbol, $shortCommandIdentifier, $description]);
                }
                $this->outputLine();
            }
        }
    }

    /**
     * Render help text for a single command
     *
     * @param Command $command
     * @return void
     */
    protected function displayHelpForCommand(Command $command)
    {
        $this->outputLine();
        $this->outputLine('<u>' . $command->getShortDescription() . '</u>');
        $this->outputLine();

        $this->outputLine('<b>COMMAND:</b>');
        $name = '<i>' . $command->getCommandIdentifier() . '</i>';
        $this->outputLine('%-2s%s', [' ', $name]);

        $commandArgumentDefinitions = $command->getArgumentDefinitions();
        $usage = '';
        $hasOptions = false;
        /** @var CommandArgumentDefinition $commandArgumentDefinition */
        foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
            if (!$commandArgumentDefinition->isRequired()) {
                $hasOptions = true;
            } else {
                $usage .= sprintf(' <%s>', strtolower(preg_replace('/([A-Z])/', ' $1', $commandArgumentDefinition->getName())));
            }
        }

        $usage = $this->commandManager->getShortestIdentifierForCommand($command) . ($hasOptions ? ' [<options>]' : '') . $usage;

        $this->outputLine();
        $this->outputLine('<b>USAGE:</b>');
        $this->outputLine('  %s %s', [$this->getFlowInvocationString(), $usage]);

        $argumentDescriptions = [];
        $optionDescriptions = [];

        if ($command->hasArguments()) {
            foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
                $argumentDescription = $commandArgumentDefinition->getDescription();
                $argumentDescription = wordwrap($argumentDescription, $this->output->getMaximumLineLength() - 23, PHP_EOL . str_repeat(' ', 23), true);
                if ($commandArgumentDefinition->isRequired()) {
                    $argumentDescriptions[] = vsprintf('  %-20s %s', [$commandArgumentDefinition->getDashedName(), $argumentDescription]);
                } else {
                    $optionDescriptions[] = vsprintf('  %-20s %s', [$commandArgumentDefinition->getDashedName(), $argumentDescription]);
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
                $this->outputLine('%-2s%s', [' ', $descriptionLine]);
            }
        }

        $relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
        if ($relatedCommandIdentifiers !== []) {
            $this->outputLine();
            $this->outputLine('<b>SEE ALSO:</b>');
            foreach ($relatedCommandIdentifiers as $commandIdentifier) {
                try {
                    $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
                    $this->outputLine('%-2s%s (%s)', [' ', $commandIdentifier, $command->getShortDescription()]);
                } catch (CommandException $exception) {
                    $this->outputLine('%-2s%s (%s)', [' ', $commandIdentifier, '<i>Command not available</i>']);
                }
            }
        }

        $this->outputLine();
    }

    /**
     * Displays an error message
     *
     * @Flow\Internal
     * @param CommandException $exception
     * @return void
     */
    public function errorCommand(CommandException $exception)
    {
        $this->outputLine($exception->getMessage());
        if ($exception instanceof AmbiguousCommandIdentifierException) {
            $this->outputLine('Please specify the complete command identifier. Matched commands:');
            $this->displayShortHelpForCommands($exception->getMatchingCommands());
        }
        $this->outputLine();
        $this->outputLine('Enter "%s help" for an overview of all available commands', [$this->getFlowInvocationString()]);
        $this->outputLine('or "%s help <commandIdentifier>" for a detailed description of the corresponding command.', [$this->getFlowInvocationString()]);
    }

    /**
     * Builds an index of available commands. For each of them a Command object is
     * added to the commands array of this class.
     *
     * @param array<Command> $commands
     * @return array in the format array('<packageKey>' => array('<CommandControllerClassName>', array('<command1>' => $command1, '<command2>' => $command2)))
     */
    protected function buildCommandsIndex(array $commands)
    {
        $commandsByPackagesAndControllers = [];
        /** @var Command $command */
        foreach ($commands as $command) {
            if ($command->isInternal()) {
                continue;
            }
            $commandIdentifier = $command->getCommandIdentifier();
            $packageKey = strstr($commandIdentifier, ':', true);
            $commandControllerClassName = $command->getControllerClassName();
            $commandName = $command->getControllerCommandName();
            $commandsByPackagesAndControllers[$packageKey][$commandControllerClassName][$commandName] = $command;
        }
        return $commandsByPackagesAndControllers;
    }
}
