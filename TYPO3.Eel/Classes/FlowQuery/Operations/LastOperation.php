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
 * Get the last element inside the context
 */
class LastOperation extends AbstractOperation {

	static protected $shortName = 'last';

	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		$context = $flowQuery->getContext();
		if (count($context) > 0) {
			$flowQuery->setContext(array(end($context)));
		} else {
			$flowQuery->setContext(array());
		}
	}
}

?>