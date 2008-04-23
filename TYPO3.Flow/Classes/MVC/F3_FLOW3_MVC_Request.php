<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_FLOW3_MVC_Request.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Represents a generic request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Request.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Request {

	/**
	 * @var boolean If this request object is locked for write access or not
	 */
	protected $locked = FALSE;

	/**
	 * @var string Contains the name of the request method
	 */
	protected $method;

	/**
	 * @var ArrayObject The arguments for this request
	 */
	protected $arguments;

	/**
	 * @var string Component name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'F3_FLOW3_MVC_Controller_DefaultController';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $actionName = 'default';

	/**
	 * Constructs this request
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = new ArrayObject;
	}

	/**
	 * Sets the name of the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked if this request object is already locked
	 */
	public function setMethod($method) {
		if ($this->locked) throw new F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked('This request object is locked for write access.', 1181134253);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked if this request object is already locked
	 */
	public function setArgument($argumentName, $value) {
		if ($this->locked) throw new F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked('This request object is locked for write access.', 1181134254);
		if (!is_string($argumentName) || F3_PHP6_Functions::strlen($argumentName) == 0) throw new F3_FLOW3_MVC_Exception_InvalidArgumentName();
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param ArrayObject $arguments An ArrayObject of argument names and their values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked if this request object is already locked
	 */
	public function setArguments(ArrayObject $arguments) {
		if ($this->locked) throw new F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked('This request object is locked for write access.', 1181134255);
		$this->arguments = $arguments;
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return ArrayObject ArrayObject of arguments and their values (which may be arguments and values as well)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_NoSuchArgument if such an argument does not exist
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new F3_FLOW3_MVC_Exception_NoSuchArgument('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
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
	 * Sets the component name of the controller which is supposed to handle the request.
	 *
	 * @param string $controllerName Component name of the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked if this request object is already locked.
	 */
	public function setControllerName($controllerName) {
		if ($this->locked) throw new F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked('This request object is locked for write access.', 1183444614);
		if (!is_string($controllerName)) throw new F3_FLOW3_MVC_Exception_InvalidControllerName('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		$this->controllerName = $controllerName;
	}

	/**
	 * Returns the component name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Component name of the controller
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerName() {
		return $this->controllerName;
	}

	/**
	 * Sets the name of the action contained in this request
	 *
	 * @param string $actionName: Name of the action to execute by the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked if this request object is already locked.
	 */
	public function setActionName($actionName) {
		if ($this->locked) throw new F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked('This request object is locked for write access.', 1186648993);
		if (!is_string($actionName)) throw new F3_FLOW3_MVC_Exception_InvalidActionName('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		$this->actionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getActionName() {
		return $this->actionName;
	}


	/**
	 * Locks this request object so no properties can be changed from
	 * outside anymore.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked if this request object is already locked.
	 */
	public function lock() {
		if ($this->locked) throw new F3_FLOW3_MVC_Exception_RequestObjectAlreadyLocked('This request object is locked for write access.', 1181135201);
		$this->locked = TRUE;
	}
}
?>