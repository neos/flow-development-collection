<?php
namespace F3\FLOW3\MVC\CLI;

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

use \F3\FLOW3\MVC\CLI\Command;

/**
 * Builds a CLI request object from the raw command call
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class RequestBuilder {

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \F3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Builds a CLI request object from a command line.
	 *
	 * The given command line may be a string (e.g. "mypackage:foo do-that-thing --force") or
	 * an array consisting of the individual parts. The array must not include the script
	 * name (like in $argv) but start with command right away.
	 *
	 * @param mixed $commandLine The command line, either as a string or as an array
	 * @return \F3\FLOW3\MVC\CLI\Request The CLI request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build($commandLine) {
		$request = new Request();
		$request->setControllerObjectName('F3\FLOW3\Command\HelpCommandController');
		$request->setControllerCommandName('help');

		$rawCommandLineArguments = is_array($commandLine) ? $commandLine : explode(' ', $commandLine);
		if (count($rawCommandLineArguments) === 0) {
			return $request;
		}

		$controllerNameParts = explode(':', trim(array_shift($rawCommandLineArguments)));
		if (count($controllerNameParts) !== 3) {
			return $request;
		}

		list($packageKey, $controllerName, $controllerCommandName) = $controllerNameParts;
		$packageNamespace = $this->resolvePackageNamespace($packageKey);

		if ($packageNamespace === FALSE) {
			return $request;
		}

		$lowerCasesControllerObjectName = strtolower(sprintf('%s\\Command\\%sCommandController', $packageNamespace, $controllerName));
		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($lowerCasesControllerObjectName);
		if ($controllerObjectName === FALSE) {
			return $request;
		}

		$request->setControllerObjectName($controllerObjectName);
		$request->setControllerCommandName($controllerCommandName);

		$commandLineArguments = $this->parseRawCommandLineArguments($rawCommandLineArguments);
		$request->setArguments($commandLineArguments['options']);
		$request->setCommandLineArguments($commandLineArguments['arguments']);

		return $request;
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
	 * @param \F3\FLOW3\MVC\CLI\Request $request
	 * @param array $command
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setControllerOptions(\F3\FLOW3\MVC\CLI\Request $request, array $command) {
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
	 *
	 * @param array $rawCommandLineArguments
	 * @return array
	 */
	protected function parseRawCommandLineArguments(array $rawCommandLineArguments) {
		$commandLineArguments = array('options' => array(), 'arguments' => array());

		$onlyArgumentsFollow = FALSE;

		while (count($rawCommandLineArguments) > 0) {

			$rawArgument = array_shift($rawCommandLineArguments);

			if ($rawArgument === '--') {
				$onlyArgumentsFollow = TRUE;
				continue;
			}

			if ($onlyArgumentsFollow) {
				$commandLineArguments['arguments'][] = $rawArgument;
			} else {
				if ($rawArgument[0] === '-') {
					if ($rawArgument[1] === '-') {
							// long option (--blah=hurz)
						$rawArgument = substr($rawArgument, 2);
					} else {
							// short option (-b hurz)
						$rawArgument = substr($rawArgument, 1);
					}
					$optionName = $this->convertCommandLineOptionToRequestArgumentName($rawArgument);
					$optionValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments);
					$commandLineArguments['options'][$optionName] = $optionValue;
				} else {
					$commandLineArguments['arguments'][] = $rawArgument;
				}
			}
		}

		return $commandLineArguments;
	}

	/**
	 * Converts the first element of the input to an argument name for a \F3\FLOW3\MVC\RequestInterface object.
	 *
	 * @param string $commandLineOption the command line option
	 * @return string converted argument name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function convertCommandLineOptionToRequestArgumentName($commandLineOption) {
		$explodedOption = explode('=', $commandLineOption, 2);
		$argumentName = explode('-', $explodedOption[0]);
		$convertedName = '';

		foreach ($argumentName as $part) {
			$convertedName .= ($convertedName !== '') ? ucfirst($part) : $part;
		}

		return $convertedName;
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