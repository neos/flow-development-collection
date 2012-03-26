<?php
namespace TYPO3\FLOW3\Mvc\Routing\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A mock RoutePartHandler
 *
 */
class MockRoutePartHandler extends \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePart {

	protected function matchValue($value) {
		$this->value = '_match_invoked_';
		return TRUE;
	}

	protected function resolveValue($value) {
		$this->value = '_resolve_invoked_';
		return TRUE;
	}
}
?>