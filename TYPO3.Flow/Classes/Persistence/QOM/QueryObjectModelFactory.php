<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\QOM;

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
 * The Query Object Model Factory
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class QueryObjectModelFactory {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Constructs the QOM Factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Creates a query with one or more selectors.
	 *
	 * If the query is invalid, this method throws an InvalidQueryException.
	 * See the individual QOM factory methods for the validity criteria of each
	 * query element.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Selector $source the selector; non-null
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint the constraint, or null if none
	 * @param array $orderings zero or more orderings; null is equivalent to a zero-length array
	 * @param array $columns the columns; null is equivalent to a zero-length array
	 * @return \F3\FLOW3\Persistence\QOM\QueryObjectModel the query; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function createQuery(\F3\FLOW3\Persistence\QOM\Selector $source, $constraint, array $orderings, array $columns) {
		$query =  $this->objectFactory->create('F3\FLOW3\Persistence\QOM\QueryObjectModel', $source, $constraint, $orderings, $columns);
		$query->setSession($this->session);
		return $query;
	}

	/**
	 * Selects a subset of the objects in the persistence layer based on
	 * $className.
	 *
	 * @param string $className the name of the required class; non-null
	 * @param string $selectorName the selector name; optional
	 * @return \F3\FLOW3\Persistence\QOM\Selector the selector
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function selector($className, $selectorName = '') {
		if ($selectorName === '') {
			$selectorName = $className;
		}
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\Selector', $selectorName, $className);
	}

	/**
	 * Performs a logical conjunction of two other constraints.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint1 the first constraint; non-null
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint2 the second constraint; non-null
	 * @return \F3\FLOW3\Persistence\QOM\LogicalAnd the And constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function _and(\F3\FLOW3\Persistence\QOM\Constraint $constraint1, \F3\FLOW3\Persistence\QOM\Constraint $constraint2) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\LogicalAnd', $constraint1, $constraint2);
	}

	/**
	 * Performs a logical disjunction of two other constraints.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint1 the first constraint; non-null
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint2 the second constraint; non-null
	 * @return \F3\FLOW3\Persistence\QOM\LogicalOr the Or constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function _or(\F3\FLOW3\Persistence\QOM\Constraint $constraint1, \F3\FLOW3\Persistence\QOM\Constraint $constraint2) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\LogicalOr', $constraint1, $constraint2);
	}

	/**
	 * Performs a logical negation of another constraint.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint the constraint to be negated; non-null
	 * @return \F3\FLOW3\Persistence\QOM\LogicalNot the Not constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function not(\F3\FLOW3\Persistence\QOM\Constraint $constraint) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\LogicalNot', $constraint);
	}

	/**
	 * Filters tuples based on the outcome of a binary operation.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand1 the first operand; non-null
	 * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
	 * @param \F3\FLOW3\Persistence\QOM\StaticOperand $operand2 the second operand; non-null
	 * @return \F3\FLOW3\Persistence\QOM\Comparison the constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function comparison(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand1, $operator, \F3\FLOW3\Persistence\QOM\StaticOperand $operand2) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\Comparison', $operand1, $operator, $operand2);
	}

	/**
	 * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return \F3\FLOW3\Persistence\QOM\PropertyValue the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function propertyValue($propertyName, $selectorName = '') {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\PropertyValue', $propertyName, $selectorName);
	}

	/**
	 * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand the operand whose value is converted to a lower-case string; non-null
	 * @return \F3\FLOW3\Persistence\QOM\LowerCase the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function lowerCase(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\LowerCase', $operand);
	}

	/**
	 * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand the operand whose value is converted to a upper-case string; non-null
	 * @return \F3\FLOW3\Persistence\QOM\UpperCase the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function upperCase(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\UpperCase', $operand);
	}

	/**
	 * Evaluates to the value of a bind variable.
	 *
	 * @param string $bindVariableName the bind variable name; non-null
	 * @return \F3\FLOW3\Persistence\QOM\BindVariableValue the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function bindVariable($bindVariableName) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\BindVariableValue', $bindVariableName);
	}

	/**
	 * Evaluates to a literal value.
	 *
	 * The query is invalid if no value is bound to $literalValue.
	 *
	 * @param \F3\PHPCR\Value $literalValue the value
	 * @return \F3\PHPCR\Value the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function literal(\F3\PHPCR\Value $literalValue) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\Literal', $literalValue->getString());
	}

	/**
	 * Orders by the value of the specified operand, in ascending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand the operand by which to order; non-null
	 * @return \F3\FLOW3\Persistence\QOM\Ordering the ordering
	 * @api
	 */
	public function ascending(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\Ordering', $operand, \F3\FLOW3\Persistence\QOM\QueryObjectModelConstants::JCR_ORDER_ASCENDING);
	}

	/**
	 * Orders by the value of the specified operand, in descending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand the operand by which to order; non-null
	 * @return \F3\FLOW3\Persistence\QOM\Ordering the ordering
	 * @api
	 */
	public function descending(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand) {
		return $this->objectFactory->create('F3\FLOW3\Persistence\QOM\Ordering', $operand, \F3\FLOW3\Persistence\QOM\QueryObjectModelConstants::JCR_ORDER_DESCENDING);
	}

}
?>