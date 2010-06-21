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
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
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
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\LogicalAnd', $constraint1, $constraint2);
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
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\LogicalOr', $constraint1, $constraint2);
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
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\LogicalNot', $constraint);
	}

	/**
	 * Filters tuples based on the outcome of a binary operation.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand1 the first operand; non-null
	 * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
	 * @param mixed $operand2 the second operand; non-null
	 * @return \F3\FLOW3\Persistence\QOM\Comparison the constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function comparison(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand1, $operator, $operand2 = NULL) {
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\Comparison', $operand1, $operator, $operand2);
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
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\PropertyValue', $propertyName, $selectorName);
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
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\LowerCase', $operand);
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
		return $this->objectManager->create('F3\FLOW3\Persistence\QOM\UpperCase', $operand);
	}

}
?>