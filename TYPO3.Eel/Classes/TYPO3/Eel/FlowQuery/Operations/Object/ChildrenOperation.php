<?php
namespace TYPO3\Eel\FlowQuery\Operations\Object;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * "children" operation working on generic objects, using fizzle inside
 */
class ChildrenOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'children';

	/**
	 * {@inheritdoc}
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed|null if the operation is final, the return value
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (!isset($arguments[0]) || empty($arguments[0])) {
			if ($flowQuery->peekOperationName() === 'filter') {
				$filterOperation = $flowQuery->popOperation();
				if (count($filterOperation['arguments']) === 0 || empty($filterOperation['arguments'][0])) {
					throw new \TYPO3\Eel\FlowQuery\FizzleException('Filter() needs arguments if it follows an empty children(): children().filter()', 1332489382);
				}
				$selectorAndFilter = $filterOperation['arguments'][0];
			} else {
				throw new \TYPO3\Eel\FlowQuery\FizzleException('children() needs at least a Property Name filter specified, or must be followed by filter().', 1332489399);
			}
		} else {
			$selectorAndFilter = $arguments[0];
		}

		$parsedFilter = \TYPO3\Eel\FlowQuery\FizzleParser::parseFilterGroup($selectorAndFilter);

		if (count($parsedFilter['Filters']) === 0) {
			throw new \TYPO3\Eel\FlowQuery\FizzleException('filter needs to be specified in children()', 1332489416);
		} elseif (count($parsedFilter['Filters']) === 1) {
			$filter = $parsedFilter['Filters'][0];


			if (isset($filter['PropertyNameFilter'])) {
				$this->evaluatePropertyNameFilter($flowQuery, $filter['PropertyNameFilter']);
				if (isset($filter['AttributeFilters'])) {
					foreach ($filter['AttributeFilters'] as $attributeFilter) {
						$flowQuery->pushOperation('filter', array($attributeFilter['text']));
					}
				}
			} elseif (isset($filter['AttributeFilters'])) {
				throw new \TYPO3\Eel\FlowQuery\FizzleException('children() must have an Property name filter and cannot only have an attribute filter.', 1332489432);
			}
		} else {
			throw new \TYPO3\Eel\FlowQuery\FizzleException('children() only supports a single filter group right now, i.e. nothing of the form "filter1, filter2"', 1332489489);
		}
	}

	/**
	 * Evaluate the property name filter by traversing to the child object. We only support
	 * nested objects right now
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $query
	 * @param string $propertyNameFilter
	 */
	protected function evaluatePropertyNameFilter(\TYPO3\Eel\FlowQuery\FlowQuery $query, $propertyNameFilter) {
		$resultObjects = array();
		$resultObjectHashes = array();

		foreach ($query->getContext() as $element) {
			$subProperty = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($element, $propertyNameFilter);
			if (is_object($subProperty) && !isset($resultObjectHashes[spl_object_hash($subProperty)])) {
				$resultObjectHashes[spl_object_hash($subProperty)] = TRUE;
				$resultObjects[] = $subProperty;
			}
		}

		$query->setContext($resultObjects);
	}
}
?>