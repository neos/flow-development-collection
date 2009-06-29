<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Contract for a request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 * @scope prototype
 */
interface RequestInterface {

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 */
	public function setDispatched($flag);

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been disptached successfully
	 */
	public function isDispatched();

	/**
	 * Returns the object name of the controller defined by the package key and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchController if the controller does not exist
	 */
	public function getControllerObjectName();

	/**
	 * Sets the package key of the controller.
	 *
	 * @param string $packageKey The package key.
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidPackageKey if the package key is not valid
	 */
	public function setControllerPackageKey($packageKey);

	/**
	 * Returns the package key of the specified controller.
	 *
	 * @return string The package key
	 */
	public function getControllerPackageKey();

	/**
	 * Sets the subpackage key of the controller.
	 *
	 * @param string $subpackageKey The subpackage key.
	 * @return void
	 */
	public function setControllerSubpackageKey($subpackageKey);

	/**
	 * Returns the subpackage key of the specified controller.
	 *
	 * @return string The subpackage key
	 */
	public function getControllerSubpackageKey();

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 */
	public function setControllerName($controllerName);

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Object name of the controller
	 */
	public function getControllerName();

	/**
	 * Sets the name of the action contained in this request.
	 *
	 * Note that the action name must start with a lower case letter.
	 *
	 * @param string $actionName: Name of the action to execute by the controller
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidActionName if the action name is not valid
	 */
	public function setControllerActionName($actionName);

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerActionName();

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 */
	public function setArgument($argumentName, $value);

	/**
	 * Sets the whole arguments array and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 */
	public function setArguments(array $arguments);

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchArgument if such an argument does not exist
	 */
	public function getArgument($argumentName);

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 */
	public function hasArgument($argumentName);

	/**
	 * Returns an array of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 */
	public function getArguments();

	/**
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like.
	 * @return void
	 */
	public function setFormat($format);

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 */
	public function getFormat();

	/**
	 * Set the request errors that occured during the request
	 *
	 * @param array $errors An array of F3\FLOW3\Error\Error objects
	 * @return void
	 */
	public function setErrors(array $errors);

	/**
	 * Get the request errors that occured during the request
	 *
	 * @return array An array of F3\FLOW3\Error\Error objects
	 */
	public function getErrors();

}
?>
