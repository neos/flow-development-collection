<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web::Routing;

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
 * Static Route Part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class StaticRoutePart extends F3::FLOW3::MVC::Web::Routing::AbstractRoutePart {

	/**
	 * Checks whether this Static Route Part correspond to the given $urlSegments.
	 * This is TRUE if the first element of $urlSegments is not empty and is equal to the Route Part name
	 *
	 * @param array $urlSegments An array with one element per request URL segment.
	 * @return boolean TRUE if Route Part matched $urlSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function match(array &$urlSegments) {
		if ($this->name === NULL || $this->name === '') {
			return FALSE;
		}
		if (count($urlSegments) < 1) {
			return FALSE;
		}
		$valueToMatch = F3::PHP6::Functions::substr($urlSegments[0], 0, F3::PHP6::Functions::strlen($this->name));
		if ($valueToMatch != $this->name) {
			return FALSE;
		}
		$urlSegments[0] = F3::PHP6::Functions::substr($urlSegments[0], F3::PHP6::Functions::strlen($valueToMatch));
		
		if ($this->getNextRoutePartInCurrentUriPatternSegment() === NULL) {
			if (F3::PHP6::Functions::strlen($urlSegments[0]) != 0) {
				return FALSE;
			}
			array_shift($urlSegments);
		}

		return TRUE;
	}

	/**
	 * Sets the Route Part value to the Route Part name and returns TRUE.
	 *
	 * @param array $routeValues not used but needed to implement F3::FLOW3::MVC::Web::Routing::AbstractRoutePart
	 * @return boolean always TRUE
	 */
	public function resolve(array &$routeValues) {
		$this->value = $this->name;
		return TRUE;
	}
}

?>