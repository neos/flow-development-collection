<?php
namespace TYPO3\Eel\FlowQuery\Operations\Object;

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
 * Access properties of an object using ObjectAccess.
 *
 * Expects the name of a property as argument. If the context is empty, NULL
 * is returned. Otherwise the value of the property on the first context
 * element is returned.
 */
class PropertyOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'property';

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
	 * @param array $arguments the property path to use (in index 0)
	 * @return mixed
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (!isset($arguments[0]) || empty($arguments[0])) {
			throw new \TYPO3\Eel\FlowQuery\FlowQueryException('property() must be given an attribute name when used on objects, fetching all attributes is not supported.', 1332492263);
		} else {
			$context = $flowQuery->getContext();
			if (!isset($context[0])) {
				return NULL;
			}

			$element = $context[0];
			$propertyPath = $arguments[0];
			return \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($element, $propertyPath);
		}
	}
}
