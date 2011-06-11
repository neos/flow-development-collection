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

use \F3\FLOW3\MVC\RequestInterface;

/**
 * Represents a CLI request.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Request implements RequestInterface {

	/**
	 * @var string
	 */
	protected $controllerObjectName;

	/**
	 * @var string
	 */
	protected $controllerCommandName = 'default';

	/**
	 * The arguments for this request
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * If this request has been changed and needs to be dispatched again
	 * @var boolean
	 */
	protected $dispatched = FALSE;

	/**
	 *
	 * @var array
	 */
	protected $commandLineArguments;

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been disptached successfully
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Sets the object name of the controller
	 *
	 * @param string $controllerObjectName Object name of the controller which processes this request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectName($controllerObjectName) {
		$this->controllerObjectName = $controllerObjectName;
	}

	/**
	 * Returns the object name of the controller
	 *
	 * @return string The controller's object name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectName() {
		return $this->controllerObjectName;
	}

	/**
	 * Returns the command controller indentifier
	 *
	 * @return string command controller identifier, such as "flow3:core"
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCommandIdentifier() {
		$namespaceParts = explode('\\', $this->controllerObjectName);
		if (count($namespaceParts) !== 4 || $namespaceParts[0] !== 'F3' || $namespaceParts[2] !== 'Command') {
			return FALSE;
		}
		return strtolower($namespaceParts[1] . ':' . str_replace('CommandController', '', $namespaceParts[3]) . ':' . $this->controllerCommandName);
	}

	/**
	 * Sets the name of the command contained in this request.
	 *
	 * Note that the command name must start with a lower case letter and is case sensitive.
	 *
	 * @param string $commandName Name of the command to execute by the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerCommandName($commandName) {
		$this->controllerCommandName = $commandName;
	}

	/**
	 * Returns the name of the command the controller is supposed to execute.
	 *
	 * @return string Command name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerCommandName() {
		return $this->controllerCommandName;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || $argumentName === '') throw new \F3\FLOW3\MVC\Exception\InvalidArgumentNameException('Invalid argument name.', 1300893885);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchArgumentException if such an argument does not exist
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new \F3\FLOW3\MVC\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1300893886);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Get command line arguments
	 *
	 * @return array
	 */
	public function getCommandLineArguments() {
		return $this->commandLineArguments;
	}

	/**
	 * Set the command line arguments
	 *
	 * @param array $commandLineArguments
	 * @return void
	 */
	public function setCommandLineArguments(array $commandLineArguments) {
		$this->commandLineArguments = $commandLineArguments;
	}

}
?>