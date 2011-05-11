<?php
declare(ENCODING = 'utf-8');
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

		$rawCommandLineArguments = is_array($commandLine) ? $commandLine : explode(' ', $commandLine);
		if (count($rawCommandLineArguments) < 1) {
			$request->setControllerObjectName('F3\FLOW3\Command\HelpCommandController');
			$request->setControllerCommandName('help');
		} else {
			list($controllerObjectName, $controllerCommandName) = $this->parseCommandIdentifier(array_shift($rawCommandLineArguments));
			if ($controllerObjectName === FALSE) {
				$request->setControllerObjectName('F3\FLOW3\Command\HelpCommandController');
				$request->setControllerCommandName('help');
			} else {
				$request->setControllerObjectName($controllerObjectName);
				$request->setControllerCommandName($controllerCommandName);
			}

	#		$commandLineArguments = $this->parseRawCommandLineArguments($commandLineArguments);


	#		$this->setControllerOptions($request, $commandLineArguments['command']);
	#		$request->setArguments($commandLineArguments['options']);
	#		$request->setCommandLineArguments($commandLineArguments['arguments']);
		}

		return $request;
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
		$commandLineArguments = array('command' => array(), 'options' => array(), 'arguments' => array());

		$command = array();
		$onlyArgumentsFollow = FALSE;

		while (count($rawCommandLineArguments) > 0) {
			$rawArgument = array_shift($rawCommandLineArguments);

			if ($rawArgument === '--') {
				$onlyArgumentsFollow = TRUE;
				$commandHasEnded = TRUE;
				continue;
			}

			if ($onlyArgumentsFollow) {
				$commandLineArguments['arguments'][] = $rawArgument;
			} else {
				if (!$commandHasEnded && $rawArgument[0] !== '-') {
					$command[] = $rawArgument;
				} elseif ($rawArgument[0] === '-') {
					$commandHasEnded = TRUE;
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
#		$commandLineArguments['command'] = $this->buildCommandArrayFromRawCommandData($command);

		return $commandLineArguments;
	}

	/**
	 * Parse a command identifier following the scheme "packagekey:controllername:commandname" and returns the
	 * controller object name and command name.
	 *
	 * Example for a command identifier: "flow3:cache:flush"
	 *
	 * @return array Controller object name and command name
	 */
	protected function parseCommandIdentifier($commandIdentifier) {
		$controllerNameParts = explode(':', trim($commandIdentifier));
		if (count($controllerNameParts) !== 3) {
			return FALSE;
		}

		$unknownCasedControllerObjectName = sprintf('F3\\%s\\Command\\%sCommandController', $controllerNameParts[0], $controllerNameParts[1]);
		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($unknownCasedControllerObjectName);
		$controllerCommandName = $controllerNameParts[2];
		return array($controllerObjectName, $controllerCommandName);
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
		if (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && (strpos($currentArgument, '=') === FALSE)) {
			return NULL;
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

	/**
	 * Transforms the raw command data into an array.
	 *
	 * @param array $rawCommand
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildCommandArrayFromRawCommandData(array $rawCommand) {
		$command = array(
			'package' => NULL,
			'subpackages' => array(),
			'controller' => NULL,
			'action' => NULL
		);

		$command['package'] = array_shift($rawCommand);
		if (count($rawCommand) === 0) return $command;

		$command['action'] = strtolower(array_pop($rawCommand));
		$command['controller'] = array_pop($rawCommand);
		if (count($rawCommand) === 0) return $command;

		$command['subpackages'] = $rawCommand;
		return $command;
	}
}
?>