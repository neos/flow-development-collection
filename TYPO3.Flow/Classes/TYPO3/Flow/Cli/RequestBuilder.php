<?php
namespace TYPO3\Flow\Cli;

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
use TYPO3\Flow\Mvc\Exception\InvalidArgumentNameException;

/**
 * Builds a CLI request object from the raw command call
 *
 * @Flow\Scope("singleton")
 */
class RequestBuilder {

	/**
	 * This is used to parse the command line, when it's passed as a string
	 */
	const ARGUMENT_MATCHING_EXPRESSION = '/     # An argument is either...
		\'(?P<SingleQuotes>                     # a single-quoted string
			(?:\\\\\'|[^\'])*                   # (internally: contains escaped single quotes or everything not being single quotes)
		)\'
		|"(?P<DoubleQuotes>                     # OR a double-quoted string
			(?:\\\"|[^"])*                      # (internally: contains escaped double quotes or everything not being double quotes)
		)"
		|(?P<NoQuotes>                          # OR a non-quoted string
			(?:
				\\\\[ "\']                      # (internally: either the backslash escape followed by space, single or double quote or another backslash,
				|[^\'" ]                        #  or all other characters than the above ones)
			)+
		)
		/x';

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var CommandManager
	 */
	protected $commandManager;

	/**
	 * @param \TYPO3\Flow\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\Flow\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\Flow\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\Flow\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param CommandManager $commandManager
	 * @return void
	 */
	public function injectCommandManager(CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Builds a CLI request object from a command line.
	 *
	 * The given command line may be a string (e.g. "mypackage:foo do-that-thing --force") or
	 * an array consisting of the individual parts. The array must not include the script
	 * name (like in $argv) but start with command right away.
	 *
	 * @param mixed $commandLine The command line, either as a string or as an array
	 * @return \TYPO3\Flow\Cli\Request The CLI request as an object
	 */
	public function build($commandLine) {
		$request = new Request();
		$request->setControllerObjectName('TYPO3\Flow\Command\HelpCommandController');

		if (is_array($commandLine) === TRUE) {
			$rawCommandLineArguments = $commandLine;
		} else {
			preg_match_all(self::ARGUMENT_MATCHING_EXPRESSION, $commandLine, $commandLineMatchings, PREG_SET_ORDER);
			$rawCommandLineArguments = array();
			foreach ($commandLineMatchings as $match) {
				if (isset($match['NoQuotes'])) {
					$rawCommandLineArguments[] = str_replace(array('\ ', '\"', "\\'", '\\\\'), array(' ', '"', "'", '\\'), $match['NoQuotes']);
				} elseif (isset($match['DoubleQuotes'])) {
					$rawCommandLineArguments[] = str_replace('\\"', '"', $match['DoubleQuotes']);
				} elseif (isset($match['SingleQuotes'])) {
					$rawCommandLineArguments[] = str_replace('\\\'', '\'', $match['SingleQuotes']);
				} else {
					throw new InvalidArgumentNameException(sprintf('Could not parse the command line "%s" - specifically the part "%s".', $commandLine, $match[0]));
				}
			}
		}
		if (count($rawCommandLineArguments) === 0) {
			$request->setControllerCommandName('helpStub');
			return $request;
		}
		$commandIdentifier = trim(array_shift($rawCommandLineArguments));
		try {
			$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
		} catch (\TYPO3\Flow\Mvc\Exception\CommandException $exception) {
			$request->setArgument('exception', $exception);
			$request->setControllerCommandName('error');
			return $request;
		}
		$controllerObjectName = $this->objectManager->getObjectNameByClassName($command->getControllerClassName());
		$controllerCommandName = $command->getControllerCommandName();
		$request->setControllerObjectName($controllerObjectName);
		$request->setControllerCommandName($controllerCommandName);

		list($commandLineArguments, $exceedingCommandLineArguments) = $this->parseRawCommandLineArguments($rawCommandLineArguments, $controllerObjectName, $controllerCommandName);
		$request->setArguments($commandLineArguments);
		$request->setExceedingArguments($exceedingCommandLineArguments);

		return $request;
	}

	/**
	 * Takes an array of unparsed command line arguments and options and converts it separated
	 * by named arguments, options and unnamed arguments.
	 *
	 * @param array $rawCommandLineArguments The unparsed command parts (such as "--foo") as an array
	 * @param string $controllerObjectName Object name of the designated command controller
	 * @param string $controllerCommandName Command name of the recognized command (ie. method name without "Command" suffix)
	 * @return array All and exceeding command line arguments
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException
	 */
	protected function parseRawCommandLineArguments(array $rawCommandLineArguments, $controllerObjectName, $controllerCommandName) {
		$commandLineArguments = array();
		$exceedingArguments = array();
		$commandMethodName = $controllerCommandName . 'Command';
		$commandMethodParameters = $this->reflectionService->getMethodParameters($controllerObjectName, $commandMethodName);

		$requiredArguments = array();
		$optionalArguments = array();
		$argumentNames = array();
		foreach ($commandMethodParameters as $parameterName => $parameterInfo) {
			$argumentNames[] = $parameterName;
			if ($parameterInfo['optional'] === FALSE) {
				$requiredArguments[strtolower($parameterName)] = array('parameterName' => $parameterName, 'type' => $parameterInfo['type']);
			} else {
				$optionalArguments[strtolower($parameterName)] = array('parameterName' => $parameterName, 'type' => $parameterInfo['type']);
			}
		}

		$decidedToUseNamedArguments = FALSE;
		$decidedToUseUnnamedArguments = FALSE;
		$argumentIndex = 0;
		while (count($rawCommandLineArguments) > 0) {

			$rawArgument = array_shift($rawCommandLineArguments);

			if ($rawArgument !== '' && $rawArgument[0] === '-') {
				if ($rawArgument[1] === '-') {
					$rawArgument = substr($rawArgument, 2);
				} else {
					$rawArgument = substr($rawArgument, 1);
				}
				$argumentName = $this->extractArgumentNameFromCommandLinePart($rawArgument);

				if (isset($optionalArguments[$argumentName])) {
					$argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $optionalArguments[$argumentName]['type']);
					$commandLineArguments[$optionalArguments[$argumentName]['parameterName']] = $argumentValue;
				} elseif (isset($requiredArguments[$argumentName])) {
					if ($decidedToUseUnnamedArguments) {
						throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException(sprintf('Unexpected named argument "%s". If you use unnamed arguments, all required arguments must be passed without a name.', $argumentName), 1309971821);
					}
					$decidedToUseNamedArguments = TRUE;
					$argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $requiredArguments[$argumentName]['type']);
					$commandLineArguments[$requiredArguments[$argumentName]['parameterName']] = $argumentValue;
					unset($requiredArguments[$argumentName]);
				}
			} else {
				if (count($requiredArguments) > 0) {
					if ($decidedToUseNamedArguments) {
						throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentMixingException(sprintf('Unexpected unnamed argument "%s". If you use named arguments, all required arguments must be passed named.', $rawArgument), 1309971820);
					}
					$argument = array_shift($requiredArguments);
					$commandLineArguments[$argument['parameterName']] = $rawArgument;
					$decidedToUseUnnamedArguments = TRUE;
				} else {
					$exceedingArguments[] = $rawArgument;
				}
			}
			$argumentIndex ++;
		}

		return array($commandLineArguments, $exceedingArguments);
	}

	/**
	 * Extracts the option or argument name from the name / value pair of a command line.
	 *
	 * @param string $commandLinePart Part of the command line, e.g. "my-important-option=SomeInterestingValue"
	 * @return string The lowercased argument name, e.g. "myimportantoption"
	 */
	protected function extractArgumentNameFromCommandLinePart($commandLinePart) {
		$nameAndValue = explode('=', $commandLinePart, 2);
		return strtolower(str_replace('-', '', $nameAndValue[0]));
	}

	/**
	 * Returns the value of the first argument of the given input array. Shifts the parsed argument off the array.
	 *
	 * @param string $currentArgument The current argument
	 * @param array &$rawCommandLineArguments Array of the remaining command line arguments
	 * @param string $expectedArgumentType The expected type of the current argument, because booleans get special attention
	 * @return string The value of the first argument
	 */
	protected function getValueOfCurrentCommandLineOption($currentArgument, array &$rawCommandLineArguments, $expectedArgumentType) {
		if ((!isset($rawCommandLineArguments[0]) && (strpos($currentArgument, '=') === FALSE)) || (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && (strpos($currentArgument, '=') === FALSE))) {
			return TRUE;
		}

		if (strpos($currentArgument, '=') === FALSE) {
			$possibleValue = trim(array_shift($rawCommandLineArguments));
			if (strpos($possibleValue, '=') === FALSE) {
				if ($expectedArgumentType !== 'boolean') {
					return $possibleValue;
				}
				if (array_search($possibleValue, array('on', '1', 'y', 'yes', 'true', 'TRUE')) !== FALSE) {
					return TRUE;
				}
				if (array_search($possibleValue, array('off', '0', 'n', 'no', 'false', 'FALSE')) !== FALSE) {
					return FALSE;
				}
				array_unshift($rawCommandLineArguments, $possibleValue);
				return TRUE;
			}
			$currentArgument .= $possibleValue;
		}

		$splitArgument = explode('=', $currentArgument, 2);
		while ((!isset($splitArgument[1]) || trim($splitArgument[1]) === '') && count($rawCommandLineArguments) > 0) {
			$currentArgument .= array_shift($rawCommandLineArguments);
			$splitArgument = explode('=', $currentArgument);
		}

		$value = (isset($splitArgument[1])) ? $splitArgument[1] : '';
		return $value;
	}

}
