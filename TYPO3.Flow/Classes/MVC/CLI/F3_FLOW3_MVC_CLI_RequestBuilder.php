<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::CLI;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::CLI::RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Builds a CLI request object from the raw command call
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::CLI::RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestBuilder {

	/**
	 * @var F3::FLOW3::Component::ManagerInterface
	 */
	protected $componentManager;

	/**
	 * @var F3::FLOW3::Component::FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Utility::Environment
	 */
	protected $environment;

	/**
	 * Constructs the CLI Request Builder
	 *
	 * @param F3::FLOW3::Component::ManagerInterface $componentManager A reference to the component manager
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A reference to the component factory
	 * @param F3::FLOW3::Utility::Environment $environment The environment
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::FLOW3::Component::ManagerInterface $componentManager, F3::FLOW3::Component::FactoryInterface $componentFactory, F3::FLOW3::Utility::Environment $environment) {
		$this->componentManager = $componentManager;
		$this->componentFactory = $componentFactory;
		$this->environment = $environment;
	}

	/**
	 * Builds a CLI request object from the raw command call
	 *
	 * @return F3::FLOW3::MVC::CLI::Request The CLI request as an object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function build() {
		$request = $this->componentFactory->getComponent('F3::FLOW3::MVC::CLI::Request');
		if ($this->environment->getCommandLineArgumentCount() < 2) {
			$request->setControllerComponentNamePattern('F3::FLOW3::MVC::Controller::DefaultController');
			return $request;
		}

		$rawCommandLineArguments = $this->environment->getCommandLineArguments();
		array_shift($rawCommandLineArguments);
		$commandLineArguments = $this->parseRawCommandLineArguments($rawCommandLineArguments);

		$this->setControllerOptions($request, $commandLineArguments['command']);
		foreach ($commandLineArguments['options'] as $optionName => $optionValue) {
			$request->setArgument($optionName, $optionValue);
		}

		$request->setCLIArguments($commandLineArguments['arguments']);

		return $request;
	}

	/**
	 * Sets package, controller, action if found in $command
	 *
	 * @param F3::FLOW3::MVC::CLI::Request $request
	 * @param array $command
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setControllerOptions(F3::FLOW3::MVC::CLI::Request $request, array $command) {
		if ($command['package'] !== NULL) $request->setControllerPackageKey($command['package']);
		if ($command['controller'] !== NULL) $request->setControllerName($command['controller']);
		if ($command['action'] !== NULL) $request->setControllerActionName($command['action']);

		if (count($command['subpackages']) > 0) {
			$subPackages = implode('::', $command['subpackages']);
			$request->setControllerComponentNamePattern('F3::@package::' . $subPackages . '::Controller::@controllerController');
		}
	}

	/**
	 * Parses raw command line arguments and returns an array with
	 *  command => being an array with
	 *    package => package key
	 *    controller => controller name
	 *    action => action name
	 *      (if no value is found for any of those keys, it will be NULL)
	 *  options => array of name/value pairs, empty if no options found
	 *  arguments => array of values, empty if no options found
	 *
	 * @param array $rawCommandLineArguments
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseRawCommandLineArguments(array $rawCommandLineArguments) {
		$commandLineArguments = array('command' => array(), 'options' => array(), 'arguments' => array());
		$command = array();
		$commandHasEnded = FALSE;
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
				if (!$commandHasEnded && $rawArgument{0} !== '-') {
					$command[] = $rawArgument;
				} elseif ($rawArgument{0} === '-') {
					$commandHasEnded = TRUE;
					if ($rawArgument{1} === '-') {
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

		$commandLineArguments['command'] = $this->buildCommandArrayFromRawCommandData($command);

		return $commandLineArguments;
	}

	/**
	 * Converts the first element of the input array to an argument name for a F3::FLOW3::MVC::Request object.
	 *
	 * @param array array of the remaining command line arguments
	 * @return string converted argument name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function convertCommandLineOptionToRequestArgumentName($commandLineOption) {
		$explodedOption = explode('=', $commandLineOption, 2);
		$argumentName = explode('-', $explodedOption[0]);
		$convertedName = '';

		foreach ($argumentName as $part) {
			$convertedName .= ($convertedName !== '') ? F3::PHP6::Functions::ucfirst($part) : $part;
		}

		return $convertedName;
	}

	/**
	 * Returns the value of the first argument of the given input array. Shifts the parsed argument off the array.
	 *
	 * @param array Array of the remaining command line arguments
	 * @return string The value of the first argument
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function getValueOfCurrentCommandLineOption($currentArgument, array &$rawCommandLineArguments) {
		if (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0]{0} === '-' && (strpos($currentArgument, '=') === FALSE)) {
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
		while ((!isset($splitArgument[1]) || trim($splitArgument[1]) == '') && count($rawCommandLineArguments) > 0) {
			$currentArgument .= array_shift($rawCommandLineArguments);
			$splitArgument = explode('=', $currentArgument);
		}

		$value = (isset($splitArgument[1])) ? $splitArgument[1] : '';
		return $value;
	}

	/**
	 * Enter description here...
	 *
	 * @param array $command
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildCommandArrayFromRawCommandData(array $rawCommand) {
		if (count($rawCommand) === 2) throw new F3::FLOW3::MVC::Exception::InvalidFormat('CLI access needs 0, 1 or at least 3 command parts.', 1222252361);

		$command = array(
			'package' => NULL,
			'subpackages' => array(),
			'controller' => NULL,
			'action' => NULL
		);

		if (count($rawCommand) === 0) return $command;

		$command['package'] = array_shift($rawCommand);
		if (count($rawCommand) === 0) return $command;

		$command['action'] = F3::PHP6::Functions::strtolower(array_pop($rawCommand));
		$command['controller'] = array_pop($rawCommand);
		if (count($rawCommand) === 0) return $command;

		$command['subpackages'] = $rawCommand;
		return $command;
	}
}
?>