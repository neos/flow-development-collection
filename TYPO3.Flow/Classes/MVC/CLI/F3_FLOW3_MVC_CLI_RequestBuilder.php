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

		$commandLineArguments = $this->environment->getCommandLineArguments();

		if (isset($commandLineArguments[1])) $request->setControllerPackageKey($commandLineArguments[1]);
		if (isset($commandLineArguments[2])) $request->setControllerName($commandLineArguments[2]);
		if (isset($commandLineArguments[3])) $request->setControllerActionName($commandLineArguments[3]);

		$remainingArguments = array_slice($commandLineArguments, 4);

		while (count($remainingArguments) > 0) {
			$argumentName = $this->convertCurrentCommandLineArgumentToRequestArgumentName($remainingArguments);
			$argumentValue = $this->getValueOfCurrentCommandLineArgument($remainingArguments);
			if (F3::PHP6::Functions::strlen($argumentName) > 0) {
				$request->setArgument($argumentName, $argumentValue);
			}
		}

		return $request;
	}

	/**
	 * Converts the first element of the input array to an argument name for a F3::FLOW3::MVC::Request object.
	 *
	 * @param array array of the remaining command line arguments
	 * @return string converted argument name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function convertCurrentCommandLineArgumentToRequestArgumentName(&$commandLineArguments) {
		$argumentName = explode('=', $commandLineArguments[0]);
		$convertedName = '';

		foreach (explode('-', $argumentName[0]) as $part)
			$convertedName .= ($convertedName !== '' ? F3::PHP6::Functions::ucfirst($part) : $part);

		return $convertedName;
	}

	/**
	 * Returns the value of the first argument of the given input array. Shifts the parsed argument off the array.
	 *
	 * @param array Array of the remaining command line arguments
	 * @return string The value of the first argument
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function getValueOfCurrentCommandLineArgument(array &$commandLineArguments) {
		$currentArgument = array_shift($commandLineArguments);

		if (isset($commandLineArguments[0]) && preg_match('/^-/', $commandLineArguments[0]) && !preg_match('/=/', $currentArgument))
			return '';

		if (!preg_match('/--/', $currentArgument) && !preg_match('/=/', $currentArgument)) {
			$mightBeTheValue = array_shift($commandLineArguments);

			if (!preg_match('/=/', $mightBeTheValue)) return $mightBeTheValue;

			$currentArgument .= $mightBeTheValue;
		}

		$splittedArgument = explode('=', $currentArgument);
		while ((!isset($splittedArgument[1]) || trim($splittedArgument[1]) == '') && count($commandLineArguments) > 0) {
			$currentArgument .= array_shift($commandLineArguments);
			$splittedArgument = explode('=', $currentArgument);
		}

		$valueString = (isset($splittedArgument[1])) ? $splittedArgument[1] : '';
		return $valueString;
	}
}
?>