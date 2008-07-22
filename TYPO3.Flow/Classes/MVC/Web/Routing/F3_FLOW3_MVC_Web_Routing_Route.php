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
 * Standard Route
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Web_Routing_Route implements F3_FLOW3_MVC_Web_Routing_RouteInterface {

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
	 * route values.
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * Indicates whether this route is parsed.
	 * For better performance, routes are only parsed if needed.
	 *
	 * @var boolean
	 */
	protected $isParsed = FALSE;

	/**
	 * Array of F3_FLOW3_MVC_Web_Routing_AbstractRoutePart objects
	 *
	 * @var array
	 */
	protected $routeParts = array();

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * Sets default values for this Route.
	 * This array is merged with the actual values when match() is called.
	 *
	 * @param array $defaults
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setDefaults($defaults) {
		$this->defaults = $defaults;
	}

	/**
	 * Sets the URL pattern for this Route
	 *
	 * @param string $urlPattern
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUrlPattern($urlPattern) {
		$this->urlPattern = trim($urlPattern, '/ ');
	}

	/**
	 * Returns an array with the Route values.
	 *
	 * @return array
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValues() {
		return $this->values;
	}

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
	 * Checks whether $requestPath corresponds to this Route.
	 * If all Rout parts match successfully TRUE is returned and $this->values contains
	 * an array combining Route default values and calculated values from the individual Route parts.
	 *
	 * @param string $requestPath
	 * @return boolean TRUE if this Route corresponds to the given $requestPath, otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function match($requestPath) {
		$this->values = NULL;
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

		$values = array();
		foreach ($this->routeParts as $routePart) {
			if (!$routePart->match($requestPathSegments)) {
				return FALSE;
			}
			if ($routePart->getValue() !== NULL) {
				$values[$routePart->getName()] = $routePart->getValue();
			}
		}
		if (count($requestPathSegments) > 1) {
			return FALSE;
		}
		$this->values = array_merge($this->defaults, $values);
		return TRUE;
	}

	/**
	 * Iterates through all segments in $this->urlPattern and creates appropriate Rout part instances.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function parse() {
		if ($this->isParsed) {
			return;
		}
		$this->routeParts = array();
		$urlPatternSegments = explode('/', $this->urlPattern);
		foreach ($urlPatternSegments as $urlPatternSegment) {
			$routePart = $this->createRoutePartInstance($urlPatternSegment);
			$this->routeParts[] = $routePart;
		}
	}

	/**
	 * Creates corresponding Route part instance for a given $urlPatternSegment.
	 * if the segment starts and ends with two brackets "[[segment]]" it's considered to be a SubRoute part
	 * if the segment starts and ends with one bracket "[segment]" it's considered to be a dynamic Route part
	 * otherwise a static Route part instance is returned
	 *
	 * @param string $urlPatternSegment one segment of the URL pattern including brackets.
	 * @return F3_FLOW3_MVC_Web_Routing_AbstractRoutePart corresponding Route part instance
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createRoutePartInstance($urlPatternSegment) {
		$routePart = NULL;
		if (F3_PHP6_Functions::substr($urlPatternSegment, 0, 2) == '[[' && F3_PHP6_Functions::substr($urlPatternSegment, -2) == ']]') {
			$routePart = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_SubRoutePart');
			$routePartName = F3_PHP6_Functions::substr($urlPatternSegment, 2, -2);
			$routePart->setName($routePartName);
			if (isset($this->defaults[$routePartName])) {
				$routePart->setDefaultValue($this->defaults[$routePartName]);
			}
		} else if (F3_PHP6_Functions::substr($urlPatternSegment, 0, 1) == '[' && F3_PHP6_Functions::substr($urlPatternSegment, -1) == ']') {
			$routePart = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_DynamicRoutePart');
			$routePartName = F3_PHP6_Functions::substr($urlPatternSegment, 1, -1);
			$routePart->setName($routePartName);
			if (isset($this->defaults[$routePartName])) {
				$routePart->setDefaultValue($this->defaults[$routePartName]);
			}
		} else {
			$routePart = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_StaticRoutePart');
			$routePart->setName($urlPatternSegment);
		}
		return $routePart;
	}
}
?>