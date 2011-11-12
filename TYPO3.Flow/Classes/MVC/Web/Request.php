<?php
namespace TYPO3\FLOW3\MVC\Web;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Property\DataType\Uri;


/**
 * Represents a web request.
 *
 * @api
 */
class Request implements \TYPO3\FLOW3\MVC\RequestInterface {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
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
	 * The arguments for this request. They must be only simple types, no
	 * objects allowed.
	 *
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Framework-internal arguments for this request, such as __referrer.
	 * All framework-internal arguments start with double underscore (__),
	 * and are only used from within the framework. Not for user consumption.
	 * Internal Arguments can be objects, in contrast to public arguments
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
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $originalRequest = NULL;

	/**
	 * If the request is a foreward because of an error, these mapping results get filled here.
	 *
	 * @var \TYPO3\FLOW3\Error\Result
	 */
	protected $originalRequestMappingResults = NULL;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Contains the request method
	 * @var string
	 */
	protected $method = 'GET';

	/**
	 * The request URI
	 * @var \TYPO3\FLOW3\Property\DataType\Uri
	 */
	protected $requestUri;

	/**
	 * The base URI for this request - ie. the host and path leading to which all FLOW3 URI paths are relative
	 *
	 * @var \TYPO3\FLOW3\Property\DataType\Uri
	 */
	protected $baseUri;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the package manager
	 *
	 * @param \TYPO3\FLOW3\Package\PackageManagerInterface $packageManager A reference to the package manager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface $router
	 * @return void
	 */
	public function injectRouter(\TYPO3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
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
	 * @api
	 */
	public function setControllerObjectName($unknownCasedControllerObjectName) {
		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($unknownCasedControllerObjectName);

		if ($controllerObjectName === FALSE) {
			throw new \TYPO3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $unknownCasedControllerObjectName . '" is not registered.', 1268844071);
		}

		$this->controllerPackageKey = $this->objectManager->getPackageKeyByObjectName($controllerObjectName);

		$matches = array();
		$subject = substr($controllerObjectName, strlen($this->controllerPackageKey) + 1);
		preg_match('/
			^(
				Controller
			|
				(?P<subpackageKey>.+)\\\\Controller
			)
			\\\\(?P<controllerName>[a-z\\\\]+)Controller
			$/ix', $subject, $matches
		);

		$this->controllerSubpackageKey = (isset($matches['subpackageKey'])) ? $matches['subpackageKey'] : NULL;
		$this->controllerName = $matches['controllerName'];
	}

	/**
	 * Sets the package key of the controller.
	 *
	 * @param string $packageKey The package key.
	 * @return void
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidPackageKeyException if the package key is not valid
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
	 */
	public function setControllerSubpackageKey($subpackageKey) {
		$this->controllerSubpackageKey = $subpackageKey;
	}

	/**
	 * Returns the subpackage key of the specified controller.
	 * If there is no subpackage key set, the method returns NULL.
	 *
	 * @return string The subpackage key
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
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName)) throw new \TYPO3\FLOW3\MVC\Exception\InvalidControllerNameException('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		if (strpos($controllerName, '_') !== FALSE) throw new \TYPO3\FLOW3\MVC\Exception\InvalidControllerNameException('The controller name must not contain underscores.', 1217846412);
		$this->controllerName = $controllerName;
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Name of the controller
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
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidActionNameException if the action name is not valid
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName)) throw new \TYPO3\FLOW3\MVC\Exception\InvalidActionNameException('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		if ($actionName === '') throw new \TYPO3\FLOW3\MVC\Exception\InvalidActionNameException('The action name must not be an empty string.', 1289472991);
		if ($actionName[0] !== strtolower($actionName[0])) {
			throw new \TYPO3\FLOW3\MVC\Exception\InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		}
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
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
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidArgumentNameException if the given argument name is no string
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidArgumentTypeException if the given argument value is an object
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) === 0) throw new \TYPO3\FLOW3\MVC\Exception\InvalidArgumentNameException('Invalid argument name (must be a non-empty string).', 1210858767);

		if (substr($argumentName, 0, 2) === '__') {
			$this->internalArguments[$argumentName] = $value;
			return;
		}

		if (is_object($value)) throw new \TYPO3\FLOW3\MVC\Exception\InvalidArgumentTypeException('You are not allowed to store objects in the request arguments. Please convert the object of type "' . get_class($value) . '" given for argument "' . $argumentName . '" to a simple type first.', 1302783022);

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
				$this->arguments[$argumentName] = $value;
		}
	}

	/**
	 * Sets the specified arguments.
	 * The arguments array will be reset therefore any arguments
	 * which existed before will be overwritten!
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidArgumentNameException if an argument name is no string
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidArgumentTypeException if an argument value is an object
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
	 * @throws \TYPO3\FLOW3\MVC\Exception\NoSuchArgumentException if such an argument does not exist
	 * @api
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new \TYPO3\FLOW3\MVC\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @api
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an Array of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
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
	 */
	public function setFormat($format) {
		$this->format = strtolower($format);
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Returns the original request. Filled only if a property mapping error occured.
	 *
	 * @return \TYPO3\FLOW3\MVC\Web\Request the original request.
	 */
	public function getOriginalRequest() {
		return $this->originalRequest;
	}

	/**
	 * @param \TYPO3\FLOW3\MVC\Web\Request $originalRequest
	 * @return void
	 */
	public function setOriginalRequest(\TYPO3\FLOW3\MVC\Web\Request $originalRequest) {
		$this->originalRequest = $originalRequest;
	}

	/**
	 * Get the request mapping results for the original request.
	 *
	 * @return \TYPO3\FLOW3\Error\Result
	 */
	public function getOriginalRequestMappingResults() {
		if ($this->originalRequestMappingResults === NULL) {
			return new \TYPO3\FLOW3\Error\Result();
		}
		return $this->originalRequestMappingResults;
	}

	/**
	 *
	 * @param \TYPO3\FLOW3\Error\Result $originalRequestMappingResults
	 */
	public function setOriginalRequestMappingResults(\TYPO3\FLOW3\Error\Result $originalRequestMappingResults) {
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

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument, or NULL if not set.
	 */
	public function getInternalArgument($argumentName) {
		if (!isset($this->internalArguments[$argumentName])) return NULL;
		return $this->internalArguments[$argumentName];
	}

	/**
	 * Sets the Request URI
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $requestUri
	 * @return void
	 */
	public function setRequestUri(Uri $requestUri) {
		$this->requestUri = $requestUri;
	}

	/**
	 * Returns the request URI
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri URI of this web request
	 * @api
	 */
	public function getRequestUri() {
		return $this->requestUri;
	}

	/**
	 * Sets the Base URI
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $baseUri
	 * @return void
	 */
	public function setBaseUri(Uri $baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the base URI
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri URI of this web request
	 * @api
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @throws \TYPO3\FLOW3\MVC\Exception\InvalidRequestMethodException if the request method is not supported
	 * @api
	 */
	public function setMethod($method) {
		if ($method === '' || (strtoupper($method) !== $method)) throw new \TYPO3\FLOW3\MVC\Exception\InvalidRequestMethodException('The request method "' . $method . '" is not supported.', 1217778382);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 * @api
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Returns the the request path relative to the base URI
	 *
	 * @return string
	 * @api
	 */
	public function getRoutePath() {
		return substr($this->requestUri->getPath(), strlen($this->baseUri->getPath()));
	}

	/**
	 * Get a freshly built request object pointing to the Referrer.
	 *
	 * @return Request the referring request, or NULL if no referrer found
	 */
	public function getReferringRequest() {
		if (isset($this->internalArguments['__referrer']) && is_array($this->internalArguments['__referrer'])) {
			$referrerArray = $this->internalArguments['__referrer'];

			$referringRequest = new Request;

			$arguments = array();
			if (isset($referrerArray['arguments'])) {
				$arguments = unserialize($referrerArray['arguments']);
				unset($referrerArray['arguments']);
			}

			$referringRequest->setArguments(\TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $referrerArray));
			return $referringRequest;
		}
		return NULL;
	}
}
?>