<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A type-safe collection for \F3\FLOW3\MVC\Web\Routing\RoutePartCollection instances.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class UriPatternSegmentCollection extends \F3\FLOW3\Utility\GenericCollection {

	public function __construct() {
		parent::__construct('F3\FLOW3\MVC\Web\Routing\RoutePartCollection');
	}

	/**
	 * Returns the next RoutePart in the current uriPatternSegment or NULL if there are no RouteParts left.
	 *
	 * @return \F3\FLOW3\MVC\Web\Routing\AbstractRoutePart next RoutePart in current Segment or NULL if no RouteParts are left.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getNextRoutePartInCurrentUriPatternSegment() {
		$currentUriPatternSegment = $this->current();
		if ($currentUriPatternSegment === FALSE) {
			return NULL;
		}
		$currentKey = (integer)$currentUriPatternSegment->key();
		$nextKey = $currentKey + 1;
		if (!$currentUriPatternSegment->offsetExists($nextKey)) {
			return NULL;
		}
		return $currentUriPatternSegment[$nextKey];
	}
}
?>