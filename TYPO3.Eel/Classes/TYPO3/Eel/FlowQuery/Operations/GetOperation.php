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
 * Get a (non-wrapped) element from the context.
 *
 * If FlowQuery is used, the result is always another FlowQuery. In case you
 * need to pass a FlowQuery result (and lazy evaluation does not work out) you
 * can use get() to unwrap the result from the "FlowQuery envelope".
 *
 * If no arguments are given, the full context is returned. Otherwise the
 * value contained in the context at the index given as argument is
 * returned. If no such index exists, NULL is returned.
 */
class GetOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'get';

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
	 * @param array $arguments the context index to fetch from
	 * @return mixed
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		$context = $flowQuery->getContext();
		if (isset($arguments[0])) {
			$index = $arguments[0];
			if (isset($context[$index])) {
				return $context[$index];
			} else {
				return NULL;
			}
		} else {
			return $context;
		}
	}
}
