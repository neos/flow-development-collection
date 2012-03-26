<?php
namespace TYPO3\Eel\FlowQuery\Operations;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Check whether the at least one of the context elements match the given filter.
 */
class IsOperation extends AbstractOperation {

	static protected $shortName = 'is';
	static protected $final = TRUE;

	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (count($arguments) == 0) {
			return count($flowQuery->getContext()) > 0;
		} else {
			$flowQuery->pushOperation('is', array());
			$flowQuery->pushOperation('filter', $arguments);
		}
	}
}
?>