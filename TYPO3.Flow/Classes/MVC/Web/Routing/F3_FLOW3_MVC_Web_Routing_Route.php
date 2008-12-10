<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * @version $Id$
 */

/**
 * Implementation of a standard route
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Route {

	const ROUTEPART_TYPE_STATIC = 'static';
	const ROUTEPART_TYPE_DYNAMIC = 'dynamic';
	const PATTERN_EXTRACTROUTEPARTS = '/(?P<dynamic>\[?)(?P<content>@?[^\]\[]+)\]?/';

	/**
	 * Route name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Default values
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * URI Pattern of this route
	 * @var string
	 */
	protected $uriPattern;

	/**
	 * @var string
	 */
	protected $controllerObjectNamePattern = NULL;

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = NULL;

	/**
	 * Contains the routing results (indexed by "package", "controller" and "action") after a successful call of matches()
	 *
	 * @var array
	 */
	protected $matchResults = array();

	/**
	 * Contains the matching uri (excluding protocol and host) after a successful call of resolves()
	 *
	 * @var string
	 */
	protected $matchingURI;

	/**
	 * Contains associative array of custom Route Part handler classnames (key: Route Part name, value: Route Part handler classname)
	 *
	 * @var array
	 */
	protected $routePartHandlers = array();

	/**
	 * Indicates whether this route is parsed.
	 * For better performance, routes are only parsed if needed.
	 *
	 * @var boolean
	 */
	protected $isParsed = FALSE;

	/**
	 * Container for RoutePartCollections. Each element represents one URI segment and contains one or more \F3\FLOW3\MVC\Web\Routing\AbstractRoutePart object(s)
	 *
	 * @var \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection
	 */
	protected $uriPatternSegments;

	/**
	 * Container for RoutePartCollections. Each element represents one Query parameter and contains one or more \F3\FLOW3\MVC\Web\Routing\AbstractRoutePart object(s)
	 *
	 * @var \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection
	 */
	protected $uriPatternQueryParameters;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory, \F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectFactory = $objectFactory;
		$this->objectManager = $objectManager;
	}

	/**
	 * Sets Route name.
	 *
	 * @param string $name The Route name
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this Route.
	 *
	 * @return string Route name.
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setDefaults($defaults) {
		$this->defaults = $defaults;
	}

	/**
	 * Sets the URI pattern this route should match with
	 *
	 * @param string $uriPattern
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUriPattern($uriPattern) {
		if (!is_string($uriPattern)) throw new \InvalidArgumentException('URI Pattern must be of type string, ' . gettype($uriPattern) . ' given.', 1223499724);
		$this->uriPattern = trim($uriPattern, '/ ');
		$this->isParsed = FALSE;
	}

	/**
	 * Set a custom controller object name pattern which will be
	 * passed to the web request.
	 *
	 * @param string $pattern A pattern which may contain placeholders
	 * @return void
	 * @see \F3\FLOW3\MVC\Web\Request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectNamePattern($pattern) {
		$this->controllerObjectNamePattern = $pattern;
	}

	/**
	 * Returns the custom controller object name pattern.
	 *
	 * @return string The pattern or NULL if none was defined
	 * @see \F3\FLOW3\MVC\Web\Request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectNamePattern() {
		return $this->controllerObjectNamePattern;
	}

	/**
	 * Sets a custom view object name pattern which will be
	 * passed to the web request.
	 *
	 * @param string $pattern A pattern which may contain placeholders
	 * @return void
	 * @see \F3\FLOW3\MVC\Web\Request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setViewObjectNamePattern($pattern) {
		$this->viewObjectNamePattern = $pattern;
	}

	/**
	 * Returns the custom view object name pattern.
	 *
	 * @return string The pattern or NULL if none was defined
	 * @see \F3\FLOW3\MVC\Web\Request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getViewObjectNamePattern() {
		return $this->viewObjectNamePattern;
	}

	/**
	 * By default all Dynamic Route Parts are resolved by \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart.
	 * But you can specify different classes to handle particular Route Parts.
	 * Note: Route Part handler must inherit from \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart.
	 * Usage: setRoutePartHandlers(array('@controller' => 'F3\Package\Subpackage\MyRoutePartHandler'));
	 *
	 * @param array $routePartHandlers Route Part handler classnames
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRoutePartHandlers(array $routePartHandlers) {
		$this->routePartHandlers = $routePartHandlers;
	}

	/**
	 * Returns an array with the Route match results.
	 *
	 * @return array An array of Route Parts and their values for further handling by the Router
	 * @see \F3\FLOW3\MVC\Web\Routing\Router
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMatchResults() {
		return $this->matchResults;
	}

	/**
	 * Returns the uri which corresponds to this Route.
	 *
	 * @return string A string containing the corresponding uri (excluding protocol and host)
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getMatchingURI() {
		return $this->matchingURI;
	}

	/**
	 * Checks whether $requestPath corresponds to this Route.
	 * If all Route Parts match successfully TRUE is returned and $this->matchResults contains
	 * an array combining Route default values and calculated matchResults from the individual Route Parts.
	 *
	 * @param string $requestPath the request path without protocol, host and query string
	 * @param string $requestQuery the request query string (optional)
	 * @return boolean TRUE if this Route corresponds to the given $requestPath, otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matches($requestPath, $requestQuery = NULL) {
		$this->matchResults = NULL;
		if ($requestPath === NULL) {
			return FALSE;
		}
		if ($this->uriPattern === NULL) {
			return FALSE;
		}
		if (!$this->isParsed) {
			$this->parse();
		}

		$matchResults = array();

		$requestPath = trim($requestPath, '/ ');
		$requestPathSegments = strlen($requestPath) ? explode('/', $requestPath) : array();
		foreach ($this->uriPatternSegments as $uriPatternSegment) {
			foreach ($uriPatternSegment as $routePart) {
				if (!$routePart->match($requestPathSegments)) {
					return FALSE;
				}
				if ($routePart->getValue() !== NULL) {
					$matchResults[$routePart->getName()] = $routePart->getValue();
				}
			}
		}
		if (count($requestPathSegments) > 0) {
			return FALSE;
		}

		if ($this->uriPatternQueryParameters !== NULL) {
			$requestQuery = trim($requestQuery);
			$requestQueryParameters = strlen($requestQuery) ? explode('&', $requestQuery) : array();
			foreach ($this->uriPatternQueryParameters as $uriPatternQueryParameter) {
				foreach ($uriPatternQueryParameter as $routePart) {
					if (!$routePart->match($requestQueryParameters)) {
						return FALSE;
					}
					if ($routePart->getValue() !== NULL) {
						$matchResults[$routePart->getName()] = $routePart->getValue();
					}
				}
			}
			if (count($requestQueryParameters) > 0) {
				return FALSE;
			}
		}

		$this->matchResults = array_merge($this->defaults, $matchResults);
		return TRUE;
	}

	/**
	 * Checks whether $routeValues can be resolved to a corresponding uri.
	 * If all Route Parts can resolve one or more of the $routeValues, TRUE is returned and $this->matchingURI contains
	 * the generated uri (excluding protocol and host).
	 *
	 * @param array $routeValues An array containing key/value pairs to be resolved to uri segments
	 * @return boolean TRUE if this Route corresponds to the given $routeValues, otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolves(array $routeValues) {
		$this->matchingURI = NULL;
		if ($this->uriPattern === NULL) {
			return FALSE;
		}
		if (!$this->isParsed) {
			$this->parse();
		}

		$uri = '';

		foreach ($this->uriPatternSegments as $uriPatternSegment) {
			foreach ($uriPatternSegment as $routePart) {
				if (!$routePart->resolve($routeValues)) {
					return FALSE;
				}
				$uri.= \F3\PHP6\Functions::strtolower($routePart->getValue());
			}
			$uri.= '/';
		}
		$uri = rtrim($uri, '/');

		if ($this->uriPatternQueryParameters !== NULL) {
			$uri.= '?';
			foreach ($this->uriPatternQueryParameters as $uriPatternQueryParameter) {
				foreach ($uriPatternQueryParameter as $routePart) {
					if (!$routePart->resolve($routeValues)) {
						return FALSE;
					}
					$uri.= \F3\PHP6\Functions::strtolower($routePart->getValue());
				}
				$uri.= '&';
			}
			$uri = rtrim($uri, '&');
		}

		foreach ($this->defaults as $key => $defaultValue) {
			if (isset($routeValues[$key])) {
				if ($routeValues[$key] != $defaultValue) {
					return FALSE;
				}
				unset($routeValues[$key]);
			}
		}
		if (count($routeValues) > 0) {
			return FALSE;
		}
		$this->matchingURI = $uri;
		return TRUE;
	}

	/**
	 * Iterates through all segments and query parameters in $this->uriPattern and creates appropriate Route Part instances.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function parse() {
		if ($this->isParsed) {
			return;
		}
		$this->uriPatternSegments = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$this->uriPatternQueryParameters = NULL;

		$splittedUriPattern = explode('?', $this->uriPattern);
		$uriPatternPath = $splittedUriPattern[0];
		if (isset($splittedUriPattern[1])) {
			$uriPatternQuery = $splittedUriPattern[1];
		} else {
			$uriPatternQuery = NULL;
		}

		$uriPatternSegments = explode('/', $uriPatternPath);
		foreach ($uriPatternSegments as $uriPatternSegment) {
			$this->uriPatternSegments->append($this->createRoutePartsFromUriPatternPart($uriPatternSegment, $this->uriPatternSegments));
		}

		if ($uriPatternQuery !== NULL) {
			$uriPatternQueryParameters = explode('&', $uriPatternQuery);
			$this->uriPatternQueryParameters = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
			foreach ($uriPatternQueryParameters as $uriPatternQueryParameter) {
				$this->uriPatternQueryParameters->append($this->createRoutePartsFromUriPatternPart($uriPatternQueryParameter, $this->uriPatternQueryParameters));
			}
		}
	}

	/**
	 * Creates corresponding Route Part instances for a given URI pattern fragment (either an URI pattern segment or a URI pattern query parameter).
	 * A Route Part can by dynamic or static. Dynamic Route Parts are wrapped in square brackets.
	 * One segment can contain more than one Dynamic Route Part, but they have to be separated by Static Route Parts.
	 *
	 * @param string $uriPatternPart one segment or one query parameter (name and value) of the URI pattern including brackets.
	 * @param \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection $uriPatternSegments current collection of uriPattern parts.
	 * @return \F3\FLOW3\MVC\Web\Routing\RoutePartCollection corresponding Route Part instances
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createRoutePartsFromUriPatternPart($uriPatternPart, \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection &$uriPatternSegments) {
		$routePartCollection = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\RoutePartCollection');
		$matches = array();
		preg_match_all(self::PATTERN_EXTRACTROUTEPARTS, $uriPatternPart, $matches, PREG_SET_ORDER);

		$lastRoutePartType = NULL;
		foreach ($matches as $matchIndex => $match) {
			$routePartType = empty($match['dynamic']) ? self::ROUTEPART_TYPE_STATIC : self::ROUTEPART_TYPE_DYNAMIC;
			$routePartName = $match['content'];
			if ($routePartType === self::ROUTEPART_TYPE_DYNAMIC) {
				if ($lastRoutePartType === self::ROUTEPART_TYPE_DYNAMIC) {
					throw new \F3\FLOW3\MVC\Exception\SuccessiveDynamicRouteParts('two succesive Dynamic Route Parts are not allowed!', 1218446975);
				}
			}

			$routePart = NULL;
			switch ($routePartType) {
				case self::ROUTEPART_TYPE_DYNAMIC:
					if (isset($this->routePartHandlers[$routePartName])) {
						$routePart = $this->objectManager->getObject($this->routePartHandlers[$routePartName]);
						if (!$routePart instanceof \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart) {
							throw new \F3\FLOW3\MVC\Exception\InvalidRoutePartHandler('routePart handlers must inherit from "\F3\FLOW3\MVC\Web\Routing\DynamicRoutePart"', 1218480972);
						}
					} else {
						$routePart = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\DynamicRoutePart');
					}
					if (isset($this->defaults[$routePartName])) {
						$routePart->setDefaultValue($this->defaults[$routePartName]);
					}
					break;
				case self::ROUTEPART_TYPE_STATIC:
					$routePart = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\StaticRoutePart');
			}
			$routePart->setName($routePartName);
			$routePart->setUriPatternSegments($uriPatternSegments);

			$routePartCollection->append($routePart);
			$lastRoutePartType = $routePartType;
		}

		return $routePartCollection;
	}
}

?>
