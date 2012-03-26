<?php
namespace TYPO3\FLOW3\Mvc\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Implementation of a standard route
 *
 * @copyright Copyright belongs to the respective authors
 */
class Route {

	const ROUTEPART_TYPE_STATIC = 'static';
	const ROUTEPART_TYPE_DYNAMIC = 'dynamic';
	const PATTERN_EXTRACTROUTEPARTS = '/(?P<optionalStart>\(?)(?P<dynamic>{?)(?P<content>@?[^}{\(\)]+)}?(?P<optionalEnd>\)?)/';

	/**
	 * Route name
	 *
	 * @var string
	 */
	protected $name = NULL;

	/**
	 * Default values
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * URI Pattern of this route
	 *
	 * @var string
	 */
	protected $uriPattern = NULL;

 	/**
	 * Specifies whether Route Parts of this Route should be converted to lower case when resolved.
	 *
	 * @var boolean
	 */
	protected $lowerCase = TRUE;

	/**
	 * Specifies whether Route Values, that are not part of the Routes configuration, should be appended as query string
	 *
	 * @var boolean
	 */
	protected $appendExceedingArguments = FALSE;

	/**
	 * Contains the routing results (indexed by "package", "controller" and
	 * "action") after a successful call of matches()
	 *
	 * @var array
	 */
	protected $matchResults = array();

	/**
	 * Contains the matching uri (excluding protocol and host) after a
	 * successful call of resolves()
	 *
	 * @var string
	 */
	protected $matchingUri;

	/**
	 * Contains associative array of Route Part options
	 * (key: Route Part name, value: array of Route Part options)
	 *
	 * @var array
	 */
	protected $routePartsConfiguration = array();

	/**
	 * Indicates whether this route is parsed.
	 * For better performance, routes are only parsed if needed.
	 *
	 * @var boolean
	 */
	protected $isParsed = FALSE;

	/**
	 * Container for Route Parts.
	 *
	 * @var array
	 */
	protected $routeParts = array();

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @FLOW3\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Injects the Persistence Manager
	 *
	 * @param \TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Mvc\Routing\RouterInterface $router
	 * @return void
	 */
	public function injectRouter(\TYPO3\FLOW3\Mvc\Routing\RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Sets Route name.
	 *
	 * @param string $name The Route name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this Route.
	 *
	 * @return string Route name.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets default values for this Route.
	 * This array is merged with the actual matchResults when match() is called.
	 *
	 * @param array $defaults
	 * @return void
	 */
	public function setDefaults(array $defaults) {
		$this->defaults = $defaults;
	}

	/**
	 * Returns default values for this Route.
	 *
	 * @return array Route defaults
	 */
	public function getDefaults() {
		return $this->defaults;
	}

	/**
	 * Sets the URI pattern this route should match with
	 *
	 * @param string $uriPattern
	 * @return void
	 */
	public function setUriPattern($uriPattern) {
		if (!is_string($uriPattern)) throw new \InvalidArgumentException('URI Pattern must be of type string, ' . gettype($uriPattern) . ' given.', 1223499724);
		$this->uriPattern = $uriPattern;
		$this->isParsed = FALSE;
	}

	/**
	 * Returns the URI pattern this route should match with
	 *
	 * @return string the URI pattern
	 */
	public function getUriPattern() {
		return $this->uriPattern;
	}

 	/**
	 * Specifies whether Route parts of this route should be converted to lower case when resolved.
	 * This setting can be overwritten for all dynamic Route parts.
	 *
	 * @param boolean $lowerCase TRUE: Route parts are converted to lower case by default. FALSE: Route parts are not altered.
	 * @return void
	 */
	public function setLowerCase($lowerCase) {
		$this->lowerCase = (boolean)$lowerCase;
	}

	/**
	 * Getter for $this->lowerCase.
	 *
	 * @return boolean TRUE if this Route part will be converted to lower case, otherwise FALSE.
	 * @see setLowerCase()
	 */
	public function isLowerCase() {
		return $this->lowerCase;
	}

	/**
	 * Specifies whether Route values, that are not part of the Route configuration, should be appended to the
	 * Resulting URI as query string.
	 * If set to FALSE, the route won't resolve if there are route values left after iterating through all Route Part
	 * handlers and removing the matching default values.
	 *
	 * @param boolean $appendExceedingArguments TRUE: exceeding arguments will be appended to the resulting URI
	 * @return void
	 */
	public function setAppendExceedingArguments($appendExceedingArguments) {
		$this->appendExceedingArguments = (boolean)$appendExceedingArguments;
	}

	/**
	 * Returns TRUE if exceeding arguments should be appended to the URI as query string, otherwise FALSE
	 *
	 * @return boolean
	 */
	public function getAppendExceedingArguments() {
		return $this->appendExceedingArguments;
	}

	/**
	 * By default all Dynamic Route Parts are resolved by
	 * \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePart.
	 * But you can specify different classes to handle particular Route Parts.
	 *
	 * Note: Route Part handlers must implement
	 * \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface.
	 *
	 * Usage: setRoutePartsConfiguration(array('@controller' =>
	 *            array('handler' => 'TYPO3\Package\Subpackage\MyRoutePartHandler')));
	 *
	 * @param array $routePartsConfiguration Route Parts configuration options
	 * @return void
	 */
	public function setRoutePartsConfiguration(array $routePartsConfiguration) {
		$this->routePartsConfiguration = $routePartsConfiguration;
	}

	/**
	 * Returns the route parts configuration of this route
	 *
	 * @return array $routePartsConfiguration
	 */
	public function getRoutePartsConfiguration() {
		return $this->routePartsConfiguration;
	}

	/**
	 * Returns an array with the Route match results.
	 *
	 * @return array An array of Route Parts and their values for further handling by the Router
	 * @see \TYPO3\FLOW3\Mvc\Routing\Router
	 */
	public function getMatchResults() {
		return $this->matchResults;
	}

	/**
	 * Returns the uri which corresponds to this Route.
	 *
	 * @return string A string containing the corresponding uri (excluding protocol and host)
	 */
	public function getMatchingUri() {
		return $this->matchingUri;
	}

	/**
	 * Checks whether $routePath corresponds to this Route.
	 * If all Route Parts match successfully TRUE is returned and
	 * $this->matchResults contains an array combining Route default values and
	 * calculated matchResults from the individual Route Parts.
	 *
	 * @param string $routePath the route path without protocol, host and query string
	 * @return boolean TRUE if this Route corresponds to the given $routePath, otherwise FALSE
	 * @see getMatchResults()
	 */
	public function matches($routePath) {
		$this->matchResults = NULL;
		if ($routePath === NULL) {
			return FALSE;
		}
		if ($this->uriPattern === NULL) {
			return FALSE;
		}
		if (!$this->isParsed) {
			$this->parse();
		}
		$matchResults = array();

		$routePath = trim($routePath, '/');
		$skipOptionalParts = FALSE;
		$optionalPartCount = 0;
		foreach ($this->routeParts as $routePart) {
			if ($routePart->isOptional()) {
				$optionalPartCount++;
				if ($skipOptionalParts) {
					if ($routePart->getDefaultValue() === NULL) {
						return FALSE;
					}
					continue;
				}
			} else {
				$optionalPartCount = 0;
				$skipOptionalParts = FALSE;
			}
			if ($routePart->match($routePath) !== TRUE) {
				if ($routePart->isOptional() && $optionalPartCount === 1) {
					if ($routePart->getDefaultValue() === NULL) {
						return FALSE;
					}
					$skipOptionalParts = TRUE;
				} else {
					return FALSE;
				}
			}
			$routePartValue = $routePart->getValue();
			if ($routePartValue !== NULL) {
				if ($this->containsObject($routePartValue)) {
					throw new \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartValueException('RoutePart::getValue() must only return simple types after calling RoutePart::match(). RoutePart "' . get_class($routePart) . '" returned one or more objects in Route "' . $this->getName() . '".');
				}
				$matchResults = \TYPO3\FLOW3\Utility\Arrays::setValueByPath($matchResults, $routePart->getName(), $routePartValue);
			}
		}
		if (strlen($routePath) > 0) {
			return FALSE;
		}
		$this->matchResults = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->defaults, $matchResults);
		return TRUE;
	}

	/**
	 * Checks whether $routeValues can be resolved to a corresponding uri.
	 * If all Route Parts can resolve one or more of the $routeValues, TRUE is
	 * returned and $this->matchingURI contains the generated URI (excluding
	 * protocol and host).
	 *
	 * @param array $routeValues An array containing key/value pairs to be resolved to uri segments
	 * @return boolean TRUE if this Route corresponds to the given $routeValues, otherwise FALSE
	 * @see getMatchingUri()
	 */
	public function resolves(array $routeValues) {
		$this->matchingUri = NULL;
		if ($this->uriPattern === NULL) {
			return FALSE;
		}
		if (!$this->isParsed) {
			$this->parse();
		}

		$matchingUri = '';
		$mergedRouteValues = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->defaults, $routeValues);
		$requireOptionalRouteParts = FALSE;
		$matchingOptionalUriPortion = '';
		foreach ($this->routeParts as $routePart) {
			if (!$routePart->resolve($routeValues)) {
				if (!$routePart->hasDefaultValue()) {
					return FALSE;
				}
			}
			$routePartValue = NULL;
			if ($routePart->hasValue()) {
				$routePartValue = $routePart->getValue();
				if (!is_string($routePartValue)) {
					throw new \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartValueException('RoutePart::getValue() must return a string after calling RoutePart::resolve(), got ' . (is_object($routePartValue) ? get_class($routePartValue) : gettype($routePartValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
				}
			}
			$routePartDefaultValue = $routePart->getDefaultValue();
			if ($routePartDefaultValue !== NULL && !is_string($routePartDefaultValue)) {
				throw new \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartValueException('RoutePart::getDefaultValue() must return a string, got ' . (is_object($routePartDefaultValue) ? get_class($routePartDefaultValue) : gettype($routePartDefaultValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
			}
			if (!$routePart->isOptional()) {
				$matchingUri .= $routePart->hasValue() ? $routePartValue : $routePartDefaultValue;
				$requireOptionalRouteParts = FALSE;
				continue;
			}
			if ($routePart->hasValue() && $routePartValue !== $routePartDefaultValue) {
				$matchingOptionalUriPortion .= $routePartValue;
				$requireOptionalRouteParts = TRUE;
			} else {
				$matchingOptionalUriPortion .= $routePartDefaultValue;
			}
			if ($requireOptionalRouteParts) {
				$matchingUri .= $matchingOptionalUriPortion;
				$matchingOptionalUriPortion = '';
			}
		}

		if ($this->compareAndRemoveMatchingDefaultValues($this->defaults, $routeValues) !== TRUE) {
			return FALSE;
		}
		if (isset($routeValues['@format']) && $routeValues['@format'] === '') {
			unset($routeValues['@format']);
		}

		$this->throwExceptionIfTargetControllerDoesNotExist($mergedRouteValues);

			// add query string
		if (count($routeValues) > 0) {
			$routeValues = \TYPO3\FLOW3\Utility\Arrays::removeEmptyElementsRecursively($routeValues);
			$routeValues = $this->persistenceManager->convertObjectsToIdentityArrays($routeValues);
			if (!$this->appendExceedingArguments) {
				$internalArguments = $this->extractInternalArguments($routeValues);
				if ($routeValues !== array()) {
					return FALSE;
				}
				$routeValues = $internalArguments;
			}
			$queryString = http_build_query($routeValues, NULL, '&');
			if ($queryString !== '') {
				$matchingUri .= '?' . $queryString;
			}
		}
		$this->matchingUri = $matchingUri;
		return TRUE;
	}

	/**
	 * Recursively iterates through the defaults of this route.
	 * If a route value is equal to a default value, it's removed
	 * from $routeValues.
	 * If a value exists but is not equal to is corresponding default,
	 * iteration is interrupted and FALSE is returned.
	 *
	 * @param array $defaults
	 * @param array $routeValues
	 * @return boolean FALSE if one of the $routeValues is not equal to it's default value. Otherwise TRUE
	 */
	protected function compareAndRemoveMatchingDefaultValues(array $defaults, array &$routeValues) {
		foreach ($defaults as $key => $defaultValue) {
			if (isset($routeValues[$key])) {
				if (is_array($defaultValue)) {
					if (!is_array($routeValues[$key])) {
						return FALSE;
					}
					return $this->compareAndRemoveMatchingDefaultValues($defaultValue, $routeValues[$key]);
				} elseif (is_array($routeValues[$key])) {
					return FALSE;
				}
				if (strtolower($routeValues[$key]) !== strtolower($defaultValue)) {
					return FALSE;
				}
				unset($routeValues[$key]);
			}
		}
		return TRUE;
	}

	/**
	 * Removes all internal arguments (prefixed with two underscores) from the given $arguments
	 * and returns them as array
	 *
	 * @param array $arguments
	 * @return array the internal arguments
	 */
	protected function extractInternalArguments(array &$arguments) {
		$internalArguments = array();
		foreach($arguments as $argumentKey => &$argumentValue) {
			if (substr($argumentKey, 0, 2) === '__') {
				$internalArguments[$argumentKey] = $argumentValue;
				unset($arguments[$argumentKey]);
			}
			if (is_array($argumentValue)) {
				$internalArguments[$argumentKey] = $this->extractInternalArguments($argumentValue);
				if ($internalArguments[$argumentKey] === array()) {
					unset($internalArguments[$argumentKey]);
				}
				if ($argumentValue === array()) {
					unset($arguments[$argumentKey]);
				}
			}
		}
		return $internalArguments;
	}

	/**
	 * Try to get the controller object name from the given $routeValues and throw an exception, if it can't be resolved.
	 *
	 * @throws Exception\InvalidControllerException
	 * @param array $routeValues
	 * @return void
	 */
	protected function throwExceptionIfTargetControllerDoesNotExist(array $routeValues) {
		$packageKey = isset($routeValues['@package']) ? $routeValues['@package'] : '';
		$subPackageKey = isset($routeValues['@subpackage']) ? $routeValues['@subpackage'] : '';
		$controllerName = isset($routeValues['@controller']) ? $routeValues['@controller'] : '';
		$controllerObjectName = $this->router->getControllerObjectName($packageKey, $subPackageKey, $controllerName);
		if ($controllerObjectName === NULL) {
			throw new Exception\InvalidControllerException('No controller object was found for package "' . $packageKey . '", subpackage "' . $subPackageKey . '", controller "' . $controllerName . '" in route "' . $this->getName() . '".', 1301650951);
		}
	}

	/**
	 * Checks if the given subject contains an object
	 *
	 * @param mixed $subject
	 * @return boolean If it contains an object or not
	 */
	protected function containsObject($subject) {
		if (is_object($subject)) {
			return TRUE;
		}
		if (!is_array($subject)) {
			return FALSE;
		}
		foreach ($subject as $key => $value) {
			if ($this->containsObject($value)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Iterates through all segments in $this->uriPattern and creates
	 * appropriate RoutePart instances.
	 *
	 * @return void
	 */
	public function parse() {
		if ($this->isParsed || $this->uriPattern === NULL || $this->uriPattern === '') {
			return;
		}
		$this->routeParts = array();
		$currentRoutePartIsOptional = FALSE;
		if (substr($this->uriPattern, -1) === '/') {
			throw new \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" ends with a slash, which is not allowed. You can put the trailing slash in brackets to make it optional.', 1234782997);
		}
		if ($this->uriPattern[0] === '/') {
			throw new \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" starts with a slash, which is not allowed.', 1234782983);
		}

		$matches = array();
		preg_match_all(self::PATTERN_EXTRACTROUTEPARTS, $this->uriPattern, $matches, PREG_SET_ORDER);

		$lastRoutePart = NULL;
		foreach ($matches as $match) {
			$routePartType = empty($match['dynamic']) ? self::ROUTEPART_TYPE_STATIC : self::ROUTEPART_TYPE_DYNAMIC;
			$routePartName = $match['content'];
			if (!empty($match['optionalStart'])) {
				if ($lastRoutePart !== NULL && $lastRoutePart->isOptional()) {
					throw new \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException('the URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains successive optional Route sections, which is not allowed.', 1234562050);
				}
				$currentRoutePartIsOptional = TRUE;
			}
			$routePart = NULL;
			switch ($routePartType) {
				case self::ROUTEPART_TYPE_DYNAMIC:
					if ($lastRoutePart instanceof \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface) {
						throw new \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException('the URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains successive Dynamic Route Parts, which is not allowed.', 1218446975);
					}
					if (isset($this->routePartsConfiguration[$routePartName]['handler'])) {
						$routePart = $this->objectManager->get($this->routePartsConfiguration[$routePartName]['handler']);
						if (!$routePart instanceof \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface) {
							throw new \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartHandlerException('routePart handlers must implement "\TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface" in route "' . $this->getName() . '"', 1218480972);
						}
					} elseif (isset($this->routePartsConfiguration[$routePartName]['objectType'])) {
						$routePart = new \TYPO3\FLOW3\Mvc\Routing\IdentityRoutePart();
						$routePart->setObjectType($this->routePartsConfiguration[$routePartName]['objectType']);
						if (isset($this->routePartsConfiguration[$routePartName]['uriPattern'])) {
							$routePart->setUriPattern($this->routePartsConfiguration[$routePartName]['uriPattern']);
						}
					} else {
						$routePart = new \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePart();
					}
					$routePartDefaultValue = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($this->defaults, $routePartName);
					if ($routePartDefaultValue !== NULL) {
						$routePart->setDefaultValue($routePartDefaultValue);
					}
					break;
				case self::ROUTEPART_TYPE_STATIC:
					$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
					if ($lastRoutePart !== NULL && $lastRoutePart instanceof \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface) {
						$lastRoutePart->setSplitString($routePartName);
					}
			}
			$routePart->setName($routePartName);
			$routePart->setOptional($currentRoutePartIsOptional);
			$routePart->setLowerCase($this->lowerCase);
			if (isset($this->routePartsConfiguration[$routePartName]['options'])) {
				$routePart->setOptions($this->routePartsConfiguration[$routePartName]['options']);
			}
			if (isset($this->routePartsConfiguration[$routePartName]['toLowerCase'])) {
				$routePart->setLowerCase($this->routePartsConfiguration[$routePartName]['toLowerCase']);
			}

			$this->routeParts[] = $routePart;
			if (!empty($match['optionalEnd'])) {
				if (!$currentRoutePartIsOptional) {
					throw new \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains an unopened optional section.', 1234564495);
				}
				$currentRoutePartIsOptional = FALSE;
			}
			$lastRoutePart = $routePart;
		}
		if ($currentRoutePartIsOptional) {
			throw new \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains an unterminated optional section.', 1234563922);
		}
		$this->isParsed = TRUE;
	}
}

?>
