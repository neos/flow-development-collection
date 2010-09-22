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
 * Represents a generic request.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Request implements \F3\FLOW3\MVC\RequestInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Pattern after which the controller object name is built
	 *
	 * @var string
	 */
	protected $controllerObjectNamePattern = 'F3\@package\@subpackage\Controller\@controllerController';

	/**
	 * Package key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerPackageKey = NULL;

	/**
	 * Subpackage key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerSubpackageKey = NULL;

	/**
	 * @var string Object name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Standard';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'index';

	/**
	 * @var array The arguments for this request
	 */
	protected $arguments = array();

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'txt';

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * @var array Errors that occured during this request
	 */
	protected $errors = array();

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the package manager
	 *
	 * @param \F3\FLOW3\Package\PackageManagerInterface $packageManager A reference to the package manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 * @api
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
	 * @api
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the package key and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getControllerObjectName() {
		$possibleObjectName = $this->controllerObjectNamePattern;
		$possibleObjectName = str_replace('@package', $this->controllerPackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $this->controllerSubpackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $this->controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);
		$lowercaseObjectName = strtolower($possibleObjectName);

		$objectName = $this->objectManager->getCaseSensitiveObjectName($lowercaseObjectName);
		return ($objectName !== FALSE) ? $objectName : '';
	}

	/**
	 * Explicitly sets the object name of the controller
	 *
	 * @param string $controllerObjectName The fully qualified controller object name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setControllerObjectName($controllerObjectName) {
		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($controllerObjectName);

		if ($controllerObjectName === FALSE) {
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $controllerObjectName . '" is not registered.', 1268844071);
		}

		$matches = array();
		preg_match('/
			^F3
			\\\\(?P<packageKey>[^\\\\]+)
			\\\\
			(
				Controller
			|
				(?P<subPackageKey>.+)\\\\Controller
			)
			\\\\(?P<controllerName>[a-z\\\\]+)Controller
			$/ix', $controllerObjectName, $matches
		);

		$this->controllerPackageKey = $matches['packageKey'];
		$this->controllerSubpackageKey = (isset($matches['subPackageKey'])) ? $matches['subPackageKey'] : '';
		$this->controllerName = $matches['controllerName'];
	}

	/**
	 * Sets the package key of the controller.
	 *
	 * @param string $packageKey The package key.
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidPackageKeyException if the package key is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerPackageKey($packageKey) {
		$upperCamelCasedPackageKey = $this->packageManager->getCaseSensitivePackageKey($packageKey);
		if ($upperCamelCasedPackageKey !== FALSE) {
			$this->controllerPackageKey = $upperCamelCasedPackageKey;
		} else {
			$this->controllerPackageKey = $packageKey;
		}
	}

	/**
	 * Returns the package key of the specified controller.
	 *
	 * @return string The package key
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerPackageKey() {
		return $this->controllerPackageKey;
	}

	/**
	 * Sets the subpackage key of the controller.
	 *
	 * @param string $subpackageKey The subpackage key.
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setControllerSubpackageKey($subpackageKey) {
		$this->controllerSubpackageKey = $subpackageKey;
	}

	/**
	 * Returns the subpackage key of the specified controller.
	 *
	 * @return string The subpackage key
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getControllerSubpackageKey() {
		return $this->controllerSubpackageKey;
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * Examples: "Standard", "Account", ...
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName)) throw new \F3\FLOW3\MVC\Exception\InvalidControllerNameException('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		if (strpos($controllerName, '_') !== FALSE) throw new \F3\FLOW3\MVC\Exception\InvalidControllerNameException('The controller name must not contain underscores.', 1217846412);
		$this->controllerName = $controllerName;
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Name of the controller
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerName() {
		$controllerObjectName = $this->getControllerObjectName();
		if ($controllerObjectName !== '')  {

				// Extract the controller name from the controller object name to assure that
				// the case is correct.
				// Note: Controller name can also contain sub structure like "Foo\Bar\Baz"
			return substr($controllerObjectName, -(strlen($this->controllerName)+10), -10);
		} else {
			return $this->controllerName;
		}
	}

	/**
	 * Sets the name of the action contained in this request.
	 *
	 * Note that the action name must start with a lower case letter and is case sensitive.
	 *
	 * @param string $actionName Name of the action to execute by the controller
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidActionNameException if the action name is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName)) throw new \F3\FLOW3\MVC\Exception\InvalidActionNameException('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		if ($actionName[0] !== strtolower($actionName[0])) {
			throw new \F3\FLOW3\MVC\Exception\InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		}
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerActionName() {
		$controllerObjectName = $this->getControllerObjectName();
		if ($controllerObjectName !== '' && ($this->controllerActionName === strtolower($this->controllerActionName)))  {
			$controllerClassName = $this->objectManager->getClassNameByObjectName($controllerObjectName);
			$actionMethodName = $this->controllerActionName . 'Action';
			foreach (get_class_methods($controllerClassName) as $existingMethodName) {
				if (strtolower($existingMethodName) === strtolower($actionMethodName)) {
					$this->controllerActionName = substr($existingMethodName, 0, -6);
					break;
				}
			}
     	}
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
		if (!is_string($argumentName) || strlen($argumentName) === 0) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentNameException('Invalid argument name.', 1210858767);
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
	 * @api
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new \F3\FLOW3\MVC\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like. Can even be something like "rss.xml".
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Set errors that occured during the request (e.g. argument mapping errors)
	 *
	 * @param array $errors An array of \F3\FLOW3\Error\Error objects
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setErrors(array $errors) {
		$this->errors = $errors;
	}

	/**
	 * Get errors that occured during the request (e.g. argument mapping errors)
	 *
	 * @return array The errors that occured during the request
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getErrors() {
		return $this->errors;
	}
}
?>
