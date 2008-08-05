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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Request {

	const PATTERN_MATCH_FORMAT = '/^[a-z]{1,5}$/';

	protected $controllerComponentNamePattern = 'F3_@package_Controller_@controller';

	/**
	 * @var string Package key of the controller which is supposed to handle this request.
	 */
	protected $controllerPackageKey = 'FLOW3';

	/**
	 * @var string Component name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Default';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'default';

	/**
	 * @var ArrayObject The arguments for this request
	 */
	protected $arguments;

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'txt';

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * Constructs this request
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = new ArrayObject;
	}

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
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the component name of the controller defined by the package key and
	 * controller name
	 *
	 * @return string The controller's Component Name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerComponentName() {
		$componentName = str_replace('@package', $this->controllerPackageKey, $this->controllerComponentNamePattern);
		$componentName = str_replace('@controller', $this->controllerName, $componentName);
		return $componentName;
	}

	/**
	 * Sets the pattern for building the controller component name.
	 *
	 * The pattern may contain the placeholders "@package" and "@controller" which will be substituted
	 * by the real package key and controller name.
	 *
	 * @param string $pattern The pattern
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerComponentNamePattern($pattern) {
		$this->controllerComponentNamePattern = $pattern;
	}

	/**
	 * Sets the package key of the controller.
	 *
	 * @param string $packageKey The package key.
	 * @return void
	 * @throws F3_FLOW3_MVC_Exception_InvalidPackageKey if the package key is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerPackageKey($packageKey) {
		if (!preg_match(F3_FLOW3_Package_Package::PATTERN_MATCH_PACKAGEKEY, $packageKey)) throw new F3_FLOW3_MVC_Exception_InvalidPackageKey('"' . $packageKey . '" is not a valid package key.', 1217961104);
		$this->controllerPackageKey = $packageKey;
	}

	/**
	 * Returns the package key of the specified controller.
	 *
	 * @return string The package key
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerPackageKey() {
		return $this->controllerPackageKey;
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the component name of the controller!
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName)) throw new F3_FLOW3_MVC_Exception_InvalidControllerName('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		if (strpos($controllerName, '_') !== FALSE) throw new F3_FLOW3_MVC_Exception_InvalidControllerName('The controller name must not contain underscores.', 1217846412);
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
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName)) throw new F3_FLOW3_MVC_Exception_InvalidActionName('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerActionName() {
		return $this->controllerActionName;
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
		if (!is_string($argumentName) || F3_PHP6_Functions::strlen($argumentName) == 0) throw new F3_FLOW3_MVC_Exception_InvalidArgumentName('Invalid argument name.', 1210858767);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param ArrayObject $arguments An ArrayObject of argument names and their values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArguments(ArrayObject $arguments) {
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
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFormat($format) {
		if (!preg_match(self::PATTERN_MATCH_FORMAT, $format)) throw new F3_FLOW3_MVC_Exception_InvalidFormat();
		$this->format = $format;
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFormat() {
		return $this->format;
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
}
?>