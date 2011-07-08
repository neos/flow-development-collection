<?php
namespace TYPO3\FLOW3\MVC\CLI;

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

/**
 * Builds a CLI request object from the raw command call
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class RequestBuilder {

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Builds a CLI request object from a command line.
	 *
	 * The given command line may be a string (e.g. "mypackage:foo do-that-thing --force") or
	 * an array consisting of the individual parts. The array must not include the script
	 * name (like in $argv) but start with command right away.
	 *
	 * @param mixed $commandLine The command line, either as a string or as an array
	 * @return \TYPO3\FLOW3\MVC\CLI\Request The CLI request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build($commandLine) {
		$request = new Request();
		$request->setControllerObjectName('TYPO3\FLOW3\Command\HelpCommandController');
		$request->setControllerCommandName('help');

		$rawCommandLineArguments = is_array($commandLine) ? $commandLine : explode(' ', $commandLine);
		if (count($rawCommandLineArguments) === 0) {
			return $request;
		}

		$controllerNameParts = explode(':', trim(array_shift($rawCommandLineArguments)));

		switch (count($controllerNameParts)) {
			case 3:
				list($packageKey, $controllerName, $controllerCommandName) = $controllerNameParts;
				$packageNamespace = $this->resolvePackageNamespace($packageKey);
				if ($packageNamespace === FALSE) {
					return $request;
				}
				$lowerCasesControllerObjectName = strtolower(sprintf('%s\\Command\\%sCommandController', $packageNamespace, $controllerName));
				$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($lowerCasesControllerObjectName);
			break;

			case 2:
				list($controllerName, $controllerCommandName) = $controllerNameParts;
				$controllerObjectName = $this->resolveControllerObjectName($controllerName, $controllerCommandName);
			break;

			default:
				return $request;
		}

		if ($controllerObjectName === FALSE) {
			return $request;
		}

		$request->setControllerObjectName($controllerObjectName);
		$request->setControllerCommandName($controllerCommandName);

		list($commandLineArguments, $exceedingCommandLineArguments) = $this->parseRawCommandLineArguments($rawCommandLineArguments, $controllerObjectName, $controllerCommandName);
		$request->setArguments($commandLineArguments);
		$request->setExceedingArguments($exceedingCommandLineArguments);

		return $request;
	}

	/**
	 * Tries to determine the command controller's object name from an incomplete command identifier.
	 *
	 * @param $searchedCommandControllerName Only the "name" of the controller, e.g. "help" in case of TYPO3\FLOW3\Command\HelpCommandController
	 * @param $searchedCommandName Name of the command, e.g. "listactive" in case of a method "listActiveCommand"
	 * @return string The controller object name or FALSE if the object name could not be determined
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveControllerObjectName($searchedCommandControllerName, $searchedCommandName) {
		$foundCommands = array();

		$commandControllerClassNames = $this->reflectionService->getAllSubClassNamesForClass('TYPO3\FLOW3\MVC\Controller\CommandController');
		foreach ($commandControllerClassNames as $className) {
			foreach (get_class_methods($className) as $methodName) {
				if (substr($methodName, -7, 7) === 'Command') {
					$command = new Command($className, substr($methodName, 0, -7));
					list(, $commandControllerName, $commandName) = explode(':', $command->getCommandIdentifier());
					$foundCommands[$commandControllerName . ':' . $commandName][] = $className;
				}
			}
		}
		if (!isset($foundCommands[$searchedCommandControllerName . ':' . $searchedCommandName]) ||
				count($foundCommands[$searchedCommandControllerName . ':' . $searchedCommandName]) !== 1) {
			return FALSE;
		}
		return $foundCommands[$searchedCommandControllerName . ':' . $searchedCommandName][0];
	}

	/**
	 * Returns a PHP namespace string corresponding to the given package key.
	 *
	 * If the package key contains namespace segments separated by ".", they are converted to a PHP namespace
	 * right away. If the package key is in short hand notation (ie. no "." exists, only the last namespace segment is
	 * given), this function tries to determine the full namespace by searching for any matching active package.
	 *
	 * @param string $packageKey The fully qualified or shorthand package key
	 * @return string The package namespace, or FALSE if the package key was ambiguous
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolvePackageNamespace($packageKey) {
		if (strpos($packageKey, '.') !== FALSE) {
			$packageNamespace = str_replace('.', '\\', $packageKey);
		} else {
			foreach ($this->packageManager->getActivePackages() as $package) {
				$possiblePackageNamespace = $package->getPackageNamespace();
				if (substr(strtolower($possiblePackageNamespace), - strlen($packageKey) - 1) === ('\\' . strtolower($packageKey))) {
						// Ambiguous shorthand package key:
					if (isset($packageNamespace)) {
						return FALSE;
					}
					$packageNamespace = $possiblePackageNamespace;
				}
			}
			if (!isset($packageNamespace)) {
				return FALSE;
			}
		}
		return $packageNamespace;
	}

	/**
	 * Sets package, controller, action if found in $command
	 *
	 * @param \TYPO3\FLOW3\MVC\CLI\Request $request
	 * @param array $command
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setControllerOptions(\TYPO3\FLOW3\MVC\CLI\Request $request, array $command) {
		if ($command['package'] !== NULL) {
			$request->setControllerPackageKey($command['package']);
		}
		if ($command['controller'] !== NULL) {
			$request->setControllerName($command['controller']);
		}
		if ($command['action'] !== NULL) {
			$request->setControllerActionName($command['action']);
		}

		if (count($command['subpackages']) > 0) {
			$subPackages = implode('\\', $command['subpackages']);
			$request->setControllerSubpackageKey($subPackages);
		}
	}

	/**
	 * Takes an array of unparsed command line arguments and options and converts it separated
	 * by named arguments, options and unnamed arguments.
	 *
	 * @param array $rawCommandLineArguments The unparsed command parts (such as "--foo") as an array
	 * @param string $controllerObjectName Object name of the designated command controller
	 * @param string $controllerCommandName Command name of the recognized command (ie. method name without "Command" suffix)
	 * @return array All and exceeding command line arguments
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseRawCommandLineArguments(array $rawCommandLineArguments, $controllerObjectName, $controllerCommandName) {
		$commandLineArguments = array();
		$exceedingArguments = array();
		$commandMethodName = $controllerCommandName . 'Command';
		$commandMethodParameters = $this->reflectionService->getMethodParameters($controllerObjectName, $commandMethodName);

		$requiredArgumentNames = array();
		$optionalArgumentNames = array();
		foreach ($commandMethodParameters as $parameterName => $parameterInfo) {
			if ($parameterInfo['optional'] === FALSE) {
				$requiredArgumentNames[strtolower($parameterName)] = $parameterName;
			} else {
				$optionalArgumentNames[strtolower($parameterName)] = $parameterName;
			}
		}

		$decidedToUseNamedArguments = FALSE;
		$decidedToUseUnnamedArguments = FALSE;
		while (count($rawCommandLineArguments) > 0) {

			$rawArgument = array_shift($rawCommandLineArguments);

			if ($rawArgument[0] === '-') {
				if ($rawArgument[1] === '-') {
					$rawArgument = substr($rawArgument, 2);
				} else {
					$rawArgument = substr($rawArgument, 1);
				}
				$argumentName = $this->extractArgumentNameFromCommandLinePart($rawArgument);
				$argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments);

				if (isset($optionalArgumentNames[$argumentName])) {
					$commandLineArguments[$optionalArgumentNames[$argumentName]] = $argumentValue;
				} elseif(isset($requiredArgumentNames[$argumentName])) {
					if ($decidedToUseUnnamedArguments) {
						throw new \TYPO3\FLOW3\MVC\Exception\InvalidArgumentMixingException(sprintf('Unexpected named argument "%s". If you use unnamed arguments, all required arguments must be passed without a name.', $argumentName), 1309971821);
					}
					$decidedToUseNamedArguments = TRUE;
					$commandLineArguments[$requiredArgumentNames[$argumentName]] = $argumentValue;
					unset($requiredArgumentNames[$argumentName]);
				}
			} else {
				if (count($requiredArgumentNames) > 0) {
					if ($decidedToUseNamedArguments) {
						throw new \TYPO3\FLOW3\MVC\Exception\InvalidArgumentMixingException(sprintf('Unexpected unnamed argument "%s". If you use named arguments, all required arguments must be passed named.', $rawArgument), 1309971820);
					}
					$commandLineArguments[array_shift($requiredArgumentNames)] = $rawArgument;
					$decidedToUseUnnamedArguments = TRUE;
				} else {
					$commandLineArguments[] = $rawArgument;
					$exceedingArguments[] = $rawArgument;
				}
			}
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
	 * @return string The value of the first argument
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function getValueOfCurrentCommandLineOption($currentArgument, array &$rawCommandLineArguments) {
		if (!isset($rawCommandLineArguments[0]) || (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && (strpos($currentArgument, '=') === FALSE))) {
			return TRUE;
		}

		if (strpos($currentArgument, '=') === FALSE) {
			$possibleValue = array_shift($rawCommandLineArguments);
			if (strpos($possibleValue, '=') === FALSE) {
				return $possibleValue;
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
?>