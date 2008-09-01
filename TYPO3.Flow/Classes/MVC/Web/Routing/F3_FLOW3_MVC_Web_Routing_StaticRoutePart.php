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
 * static route part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Web_Routing_StaticRoutePart extends F3_FLOW3_MVC_Web_Routing_AbstractRoutePart {

	/**
	 * @var boolean specifies whether this route part is the last in the current url segment
	 */
	protected $lastRoutePartInSegment = FALSE;

	/**
	 * After a successful match, first urlSegment is cut off. But only if this route part
	 * is the last inside the current url segment
	 *
	 * @param boolean $lastRoutePartInSegment TRUE if this is the last route part in the current url segment
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setLastRoutePartInSegment($lastRoutePartInSegment) {
		$this->lastRoutePartInSegment = $lastRoutePartInSegment;
	}

	/**
	 * Checks whether this static Route part correspond to the given $urlSegments.
	 * This is TRUE if the first element of $urlSegments is not empty and is equal to the Route part name
	 *
	 * @param array $urlSegments An array with one element per request URL segment.
	 * @return boolean TRUE if route part matched $urlSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function match(array &$urlSegments) {
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		if (count($urlSegments) < 1) {
			return FALSE;
		}
		$valueToMatch = F3_PHP6_Functions::substr($urlSegments[0], 0, F3_PHP6_Functions::strlen($this->name));
		if ($valueToMatch != $this->name) {
			return FALSE;
		}
		$urlSegments[0] = F3_PHP6_Functions::substr($urlSegments[0], F3_PHP6_Functions::strlen($valueToMatch));
		if ($this->lastRoutePartInSegment) {
			if (F3_PHP6_Functions::strlen($urlSegments[0]) != 0) {
				return FALSE;
			}
			array_shift($urlSegments);
		}

		return TRUE;
	}

	/**
	 * Sets the route part value to the route part name and returns TRUE.
	 *
	 * @param array $routeValues not used but needed to implement F3_FLOW3_MVC_Web_Routing_AbstractRoutePart
	 * @return boolean always TRUE
	 */
	public function resolve(array &$routeValues) {
		$this->value = $this->name;
		return TRUE;
	}
}

?>