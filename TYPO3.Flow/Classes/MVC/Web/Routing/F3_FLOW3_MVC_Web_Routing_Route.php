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
class F3_FLOW3_MVC_Web_Routing_Route {

	const ROUTEPART_TYPE_STATIC = 'static';
	const ROUTEPART_TYPE_DYNAMIC = 'dynamic';

	/**
	 * Default values
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * URL Pattern of this route
	 * @var string
	 */
	protected $urlPattern;

	/**
	 * @var string
	 */
	protected $controllerComponentNamePattern = NULL;

	/**
	 * Contains the routing results (indexed by "package", "controller" and "action") after a successful call of matches()
	 *
	 * @var array
	 */
	protected $matchResults = array();

	/**
	 * Contains associative array of custom route part handler classnames (key: route part name, value: route part handler classname)
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
	 * Twodimensional Array. Each element contains one or more F3_FLOW3_MVC_Web_Routing_AbstractRoutePart object(s)
	 *
	 * @var array
	 */
	protected $urlPatternSegments = array();

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
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
	 * Sets the URL pattern this route should match with
	 *
	 * @param string $urlPattern
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUrlPattern($urlPattern) {
		$this->urlPattern = trim($urlPattern, '/ ');
		$this->isParsed = FALSE;
	}

	/**
	 * Set a custom controller component name pattern which will be
	 * passed to the web request.
	 *
	 * @param string $pattern A pattern which may contain placeholders
	 * @return void
	 * @see F3_FLOW3_MVC_Web_Request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerComponentNamePattern($pattern) {
		$this->controllerComponentNamePattern = $pattern;
	}

	/**
	 * Returns the custom controller component name pattern.
	 *
	 * @return string Teh pattern or NULL if none was defined
	 * @see F3_FLOW3_MVC_Web_Request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerComponentNamePattern() {
		return $this->controllerComponentNamePattern;
	}

	/**
	 * By default all dynamic route parts are resolved by F3_FLOW3_MVC_Web_Routing_DynamicRoutePart.
	 * But you can specify different classes to handle particular route parts.
	 * Note: route part handler must inherit from F3_FLOW3_MVC_Web_Routing_DynamicRoutePart.
	 * Usage: setRoutePartHandlers(array('@controller' => 'F3_Package_Subpackage_MyRoutePartHandler'));
	 *
	 * @param array $routePartHandlers route part handler classnames
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRoutePartHandlers(array $routePartHandlers) {
		$this->routePartHandlers = $routePartHandlers;
	}

	/**
	 * Returns an array with the Route match results.
	 *
	 * @return array An array of route parts and their values for further handling by the Router
	 * @see F3_FLOW3_MVC_Web_Routing_Router
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMatchResults() {
		return $this->matchResults;
	}

	/**
	 * Checks whether $requestPath corresponds to this Route.
	 * If all Route parts match successfully TRUE is returned and $this->matchResults contains
	 * an array combining Route default values and calculated matchResults from the individual Route parts.
	 *
	 * @param string $requestPath
	 * @return boolean TRUE if this Route corresponds to the given $requestPath, otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matches($requestPath) {
		$this->matchResults = NULL;
		if ($requestPath === NULL) {
			return FALSE;
		}
		if ($this->urlPattern === NULL || $this->urlPattern == '') {
			return FALSE;
		}
		$requestPath = trim($requestPath, '/ ');
		$requestPathSegments = explode('/', $requestPath);

		if (!$this->isParsed) {
			$this->parse();
		}

		$matchResults = array();
		foreach ($this->urlPatternSegments as $urlPatternSegment) {
			foreach($urlPatternSegment as $routePart) {
				if (!$routePart->match($requestPathSegments)) {
					return FALSE;
				}
				if ($routePart->getValue() !== NULL) {
					$matchResults[$routePart->getName()] = $routePart->getValue();
				}
			}
		}
		if (count($requestPathSegments) > 1) {
			return FALSE;
		}
		$this->matchResults = array_merge($this->defaults, $matchResults);
		return TRUE;
	}

	/**
	 * Iterates through all segments in $this->urlPattern and creates appropriate route part instances.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function parse() {
		if ($this->isParsed) {
			return;
		}
		$this->urlPatternSegments = array();
		$urlPatternSegments = explode('/', $this->urlPattern);
		foreach ($urlPatternSegments as $urlPatternSegment) {
			$this->urlPatternSegments[] = $this->createRoutePartsFromUrlPatternSegment($urlPatternSegment);
		}
	}

	/**
	 * Creates corresponding Route part instances for a given $urlPatternSegment.
	 * The segment must contain at least one route part.
	 * A route part can by dynamic or static. dynamic route parts are wrapped in square brackets.
	 * One segment can contain more than one dynamic route part, but they have to be separated by static route parts.
	 *
	 * @param string $urlPatternSegment one segment of the URL pattern including brackets.
	 * @return F3_FLOW3_MVC_Web_Routing_AbstractRoutePart corresponding Route part instance
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createRoutePartsFromUrlPatternSegment($urlPatternSegment) {
		$routeParts = array();
		$pattern = '/(\[?)(@?[^\]\[]+)\]?/';
		$matches = array();
		preg_match_all($pattern, $urlPatternSegment, $matches, PREG_SET_ORDER);

		$lastRoutePartType = NULL;
		foreach($matches as $index => $match) {
			$routePartType = $match[1] == '[' ? self::ROUTEPART_TYPE_DYNAMIC : self::ROUTEPART_TYPE_STATIC;
			$routePartName = $match[2];
			$splitString = '';
			if ($routePartType === self::ROUTEPART_TYPE_DYNAMIC) {
				if ($lastRoutePartType === self::ROUTEPART_TYPE_DYNAMIC) {
					throw new F3_FLOW3_MVC_Exception_SuccessiveDynamicRouteParts('two succesive dynamic route parts are not allowed!', 1218446975);
				}
				if (($index + 1) < count($matches)) {
					$splitString = $matches[$index + 1][2];
				}
			}

			$routePart = NULL;
			switch ($routePartType) {
				case self::ROUTEPART_TYPE_DYNAMIC:
					if (isset($this->routePartHandlers[$routePartName])) {
						$routePart = $this->componentFactory->getComponent($this->routePartHandlers[$routePartName]);
						if (!$routePart instanceof F3_FLOW3_MVC_Web_Routing_DynamicRoutePart) {
							throw new F3_FLOW3_MVC_Exception_InvalidRoutePartHandler('routePart handlers must inherit from "F3_FLOW3_MVC_Web_Routing_DynamicRoutePart"', 1218480972);
						}
					} else {
						$routePart = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_DynamicRoutePart');
					}
					$routePart->setSplitString($splitString);
					if (isset($this->defaults[$routePartName])) {
						$routePart->setDefaultValue($this->defaults[$routePartName]);
					}
					break;
				default:
					$routePart = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_StaticRoutePart');
					if (($index + 1) == count($matches)) {
						$routePart->setLastRoutePartInSegment(TRUE);
					}
			}
			$routePart->setName($routePartName);
			
			$routeParts[] = $routePart;
			$lastRoutePartType = $routePartType;
		}
		
		return $routeParts;
	}
}
?>
