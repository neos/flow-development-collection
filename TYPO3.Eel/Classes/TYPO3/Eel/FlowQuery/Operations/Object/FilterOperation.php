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
 * Filter operation, limiting the set of objects. The filter expression is
 * expected as string argument and used to reduce the context to matching
 * elements by checking each value against the filter.
 *
 * A filter expression is written in Fizzle, a grammar inspired by CSS selectors.
 * It has the form `"[" [<value>] <operator> <operand> "]"` and supports the
 * following operators:
 *
 * =
 *   Strict equality of value and operand
 * !=
 *   Strict inequality of value and operand
 * $=
 *   Value ends with operand (string-based)
 * ^=
 *   Value starts with operand (string-based)
 * *=
 *   Value contains operand (string-based)
 * instanceof
 *   Checks if the value is an instance of the operand
 *
 * For the latter the behavior is as follows: if the operand is one of the strings
 * object, array, int(eger), float, double, bool(ean) or string the value is checked
 * for being of the specified type. For any other strings the value is used as
 * classname with the PHP instanceof operation to check if the value matches.
 */
class FilterOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'filter';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * {@inheritdoc}
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the filter expression to use (in index 0)
	 * @return void
	 * @throws \TYPO3\Eel\FlowQuery\FizzleException
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (!isset($arguments[0]) || empty($arguments[0])) {
			return;
		}
		if (!is_string($arguments[0])) {
			throw new \TYPO3\Eel\FlowQuery\FizzleException('filter operation expects string argument', 1332489625);
		}
		$filter = $arguments[0];

		$parsedFilter = \TYPO3\Eel\FlowQuery\FizzleParser::parseFilterGroup($filter);

		$filteredContext = array();
		$context = $flowQuery->getContext();
		foreach ($context as $element) {
			if ($this->matchesFilterGroup($element, $parsedFilter)) {
				$filteredContext[] = $element;
			}
		}
		$flowQuery->setContext($filteredContext);
	}

	/**
	 * Evaluate Filter Group. An element matches the filter group if it
	 * matches at least one part of the filter group.
	 *
	 * Filter Group is something like "[foo], [bar]"
	 *
	 * @param object $element
	 * @param array $parsedFilter
	 * @return boolean TRUE if $element matches filter group, FALSE otherwise
	 */
	protected function matchesFilterGroup($element, array $parsedFilter) {
		foreach ($parsedFilter['Filters'] as $filter) {
			if ($this->matchesFilter($element, $filter)) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Match a single filter, i.e. [foo]. It matches only if all filter parts match.
	 *
	 * @param object $element
	 * @param string $filter
	 * @return boolean TRUE if $element matches filter, FALSE otherwise
	 */
	protected function matchesFilter($element, $filter) {
		if (isset($filter['IdentifierFilter']) && !$this->matchesIdentifierFilter($element, $filter['IdentifierFilter'])) {
			return FALSE;
		}
		if (isset($filter['PropertyNameFilter']) && !$this->matchesPropertyNameFilter($element, $filter['PropertyNameFilter'])) {
			return FALSE;
		}

		if (isset($filter['AttributeFilters'])) {
			foreach ($filter['AttributeFilters'] as $attributeFilter) {
				if (!$this->matchesAttributeFilter($element, $attributeFilter)) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * For generic objects, we do not support property name filters.
	 *
	 * @param object $element
	 * @param string $propertyNameFilter
	 * @return boolean
	 * @throws \TYPO3\Eel\FlowQuery\FizzleException
	 */
	protected function matchesPropertyNameFilter($element, $propertyNameFilter) {
		throw new \TYPO3\Eel\FlowQuery\FizzleException('Property Name filter not supported for generic objects.', 1332489796);
	}

	/**
	 * Match a single attribute filter
	 *
	 * @param mixed $element
	 * @param array $attributeFilter
	 * @return boolean
	 */
	protected function matchesAttributeFilter($element, array $attributeFilter) {
		if ($attributeFilter['Identifier'] !== NULL) {
			$value = $this->getPropertyPath($element, $attributeFilter['Identifier']);
		} else {
			$value = $element;
		}
		$operand = NULL;
		if (isset($attributeFilter['Operand'])) {
			$operand = $attributeFilter['Operand'];
		}

		return $this->evaluateOperator($value, $attributeFilter['Operator'], $operand);
	}

	/**
	 * Filter the object by its identifier (UUID)
	 *
	 * @param object $element
	 * @param string $identifier
	 * @return boolean
	 */
	protected function matchesIdentifierFilter($element, $identifier) {
		return ($this->persistenceManager->getIdentifierByObject($element) === $identifier);
	}

	/**
	 * Evaluate a property path. This is outsourced to a single method
	 * to make overriding this functionality easy.
	 *
	 * @param object $element
	 * @param string $propertyPath
	 * @return mixed
	 */
	protected function getPropertyPath($element, $propertyPath) {
		return \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($element, $propertyPath);
	}

	/**
	 * Evaluate an operator
	 *
	 * @param mixed $value
	 * @param string $operator
	 * @param mixed $operand
	 * @return boolean
	 */
	protected function evaluateOperator($value, $operator, $operand) {
		switch ($operator) {
			case '=':
				return $value === $operand;
			case '!=':
				return $value !== $operand;
			case '$=':
				return strrpos($value, $operand) === strlen($value) - strlen($operand);
			case '^=':
				return strpos($value, $operand) === 0;
			case '*=':
				return strpos($value, $operand) !== FALSE;
			case 'instanceof':
				if ($this->operandIsSimpleType($operand)) {
					return $this->handleSimpleTypeOperand($operand, $value);
				} else {
					return ($value instanceof $operand);
				}
			default:
				return ($value !== NULL);
		}
	}

	/**
	 * @param string $type
	 * @return boolean TRUE if operand is a simple type (object, array, string, ...); i.e. everything which is NOT a class name
	 */
	protected function operandIsSimpleType($type) {
		return $type === 'object' || $type === 'array' || \TYPO3\Flow\Utility\TypeHandling::isLiteral($type);
	}

	/**
	 * @param string $operand
	 * @param string $value
	 * @return boolean TRUE if $value is of type $operand; FALSE otherwise
	 */
	protected function handleSimpleTypeOperand($operand, $value) {
		$operand = \TYPO3\Flow\Utility\TypeHandling::normalizeType($operand);
		if ($operand === 'object') {
			return is_object($value);
		} elseif ($operand === 'string') {
			return is_string($value);
		} elseif ($operand === 'integer') {
			return is_integer($value);
		} elseif ($operand === 'boolean') {
			return is_bool($value);
		} elseif ($operand === 'float') {
			return is_float($value);
		} elseif ($operand === 'array') {
			return is_array($value);
		}

		return FALSE;
	}
}