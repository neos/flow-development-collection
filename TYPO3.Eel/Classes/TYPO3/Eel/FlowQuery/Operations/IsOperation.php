<?php
namespace TYPO3\Eel\FlowQuery\Operations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Check whether the at least one of the context elements match the given filter.
 *
 * Without arguments is evaluates to TRUE if the context is not empty. If arguments
 * are given, they are used to filter the context before evaluation.
 */
class IsOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'is';

	/**
	 * {@inheritdoc}
	 *
	 * @var boolean
	 */
	static protected $final = TRUE;

	/**
	 * {@inheritdoc}
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the filter arguments
	 * @return boolean
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (count($arguments) == 0) {
			return count($flowQuery->getContext()) > 0;
		} else {
			$flowQuery->pushOperation('is', array());
			$flowQuery->pushOperation('filter', $arguments);
		}
	}
}
