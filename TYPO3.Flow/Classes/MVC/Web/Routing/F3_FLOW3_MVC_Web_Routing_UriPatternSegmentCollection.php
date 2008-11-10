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
 * A type-safe collection for F3::FLOW3::MVC::Web::Routing::RoutePartCollection instances.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class UriPatternSegmentCollection extends F3::FLOW3::Utility::GenericCollection {

	public function __construct() {
		parent::__construct('F3::FLOW3::MVC::Web::Routing::RoutePartCollection');
	}

	/**
	 * Returns the next RoutePart in the current uriPatternSegment or NULL if there are no RouteParts left.
	 *
	 * @return F3::FLOW3::MVC::Web::Routing::AbstractRoutePart next RoutePart in current Segment or NULL if no RouteParts are left.
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