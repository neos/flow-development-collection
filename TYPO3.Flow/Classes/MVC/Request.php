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
	 * Object name of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerName = NULL;

	/**
	 * Name of the action the controller is supposed to take.
	 *
	 * @var string
	 */
	protected $controllerActionName = NULL;

	/**
	 * The arguments for this request
	 *
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Framework-internal arguments for this request, such as __referrer.
	 * All framework-internal arguments start with double underscore (__),
	 * and are only used from within the framework. Not for user consumption.
	 *
	 * @var array
	 */
	protected $internalArguments = array();

	/**
	 * The requested representation format
	 *
	 * @var string
	 */
	protected $format;

	/**
	 * If this request has been changed and needs to be dispatched again
	 *
	 * @var boolean
	 */
	protected $dispatched = FALSE;

	/**
	 * If this request is a forward because of an error, the original request gets filled.
	 *
	 * @var \F3\FLOW3\MVC\Request
	 */
	protected $originalRequest = NULL;

	/**
	 * If the request is a foreward because of an error, these mapping results get filled here.
	 *
	 * @var \F3\FLOW3\Error\Result
	 */
	protected $originalRequestMappingResults = NULL;

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

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
	 * @param \F3\FLOW3\MVC\Web\Routing\RouterInterface $router
	 * @return void
	 */
	public function injectRouter(\F3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
		$this->router = $router;
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
		$controllerObjectName = $this->router->getControllerObjectName($this->controllerPackageKey, $this->controllerSubpackageKey, $this->controllerName);
		return ($controllerObjectName !== NULL) ? $controllerObjectName : '';
	}

	/**
	 * Explicitly sets the object name of the controller
	 *
	 * @param string $unknownCasedControllerObjectName The fully qualified controller object name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setControllerObjectName($unknownCasedControllerObjectName) {
		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($unknownCasedControllerObjectName);

		if ($controllerObjectName === FALSE) {
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $unknownCasedControllerObjectName . '" is not registered.', 1268844071);
		}

		$matches = array();
		preg_match('/
			^F3
			\\\\(?P<packageKey>[^\\\\]+)
			\\\\
			(
				Controller
			|
				(?P<subpackageKey>.+)\\\\Controller
			)
			\\\\(?P<controllerName>[a-z\\\\]+)Controller
			$/ix', $controllerObjectName, $matches
		);

		$this->controllerPackageKey = $matches['packageKey'];
		$this->controllerSubpackageKey = (isset($matches['subpackageKey'])) ? $matches['subpackageKey'] : NULL;
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
	 * If there is no subpackage key set, the method returns NULL.
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
		if ($actionName === '') throw new \F3\FLOW3\MVC\Exception\InvalidActionNameException('The action name must not be an empty string.', 1289472991);
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
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentNameException if the given argument name is no string
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentTypeException if the given argument value is an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) === 0) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentNameException('Invalid argument name (must be a non-empty string).', 1210858767);
		if (is_object($value)) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentTypeException('You are not allowed to store objects in the request arguments. Please convert the object of type "' . get_class($value) . '" given for argument "' . $argumentName . '" to a simple type first.', 1302783022);

		switch ($argumentName) {
			case '@package':
				$this->setControllerPackageKey($value);
				break;
			case '@subpackage':
				$this->setControllerSubpackageKey($value);
				break;
			case '@controller':
				$this->setControllerName($value);
				break;
			case '@action':
				$this->setControllerActionName($value);
				break;
			case '@format':
				$this->setFormat($value);
				break;
			default:
				if ($argumentName[0] === '_' && $argumentName[1] === '_') {
					$this->internalArguments[$argumentName] = $value;
				} else {
					$this->arguments[$argumentName] = $value;
				}
		}
	}

	/**
	 * Sets the specified arguments.
	 * The arguments array will be reset therefore any arguments
	 * which existed before will be overwritten!
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentNameException if an argument name is no string
	 * @throws \F3\FLOW3\MVC\Exception\InvalidArgumentTypeException if an argument value is an object
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArguments(array $arguments) {
		$this->arguments = array();
		foreach ($arguments as $key => $value) {
			$this->setArgument($key, $value);
		}
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
	 * Returns an Array of arguments and their values
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
		$this->format = strtolower($format);
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
	 * Returns the original request. Filled only if a property mapping error occured.
	 *
	 * @return \F3\FLOW3\MVC\Request the original request.
	 */
	public function getOriginalRequest() {
		return $this->originalRequest;
	}

	/**
	 * @param \F3\FLOW3\MVC\Request $originalRequest
	 * @return void
	 */
	public function setOriginalRequest(\F3\FLOW3\MVC\Request $originalRequest) {
		$this->originalRequest = $originalRequest;
	}

	/**
	 * Get the request mapping results for the original request.
	 *
	 * @return \F3\FLOW3\Error\Result
	 */
	public function getOriginalRequestMappingResults() {
		if ($this->originalRequestMappingResults === NULL) {
			return new \F3\FLOW3\Error\Result();
		}
		return $this->originalRequestMappingResults;
	}

	/**
	 *
	 * @param \F3\FLOW3\Error\Result $originalRequestMappingResults
	 */
	public function setOriginalRequestMappingResults(\F3\FLOW3\Error\Result $originalRequestMappingResults) {
		$this->originalRequestMappingResults = $originalRequestMappingResults;
	}

	/**
	 * Get the internal arguments of the request, i.e. every argument starting
	 * with two underscores.
	 *
	 * @return array
	 */
	public function getInternalArguments() {
		return $this->internalArguments;
	}
}
?>