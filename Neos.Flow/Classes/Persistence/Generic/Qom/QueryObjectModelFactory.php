<?php
namespace Neos\Flow\Persistence\Generic\Qom;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * The Query Object Model Factory
 *
 * @api
 */
class QueryObjectModelFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Injects the object factory
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Performs a logical conjunction of two other constraints.
     *
     * @param Constraint $constraint1 the first constraint; non-null
     * @param Constraint $constraint2 the second constraint; non-null
     * @return LogicalAnd the And constraint; non-null
     * @api
     */
    public function _and(Constraint $constraint1, Constraint $constraint2)
    {
        return new LogicalAnd($constraint1, $constraint2);
    }

    /**
     * Performs a logical disjunction of two other constraints.
     *
     * @param Constraint $constraint1 the first constraint; non-null
     * @param Constraint $constraint2 the second constraint; non-null
     * @return LogicalOr the Or constraint; non-null
     * @api
     */
    public function _or(Constraint $constraint1, Constraint $constraint2)
    {
        return new LogicalOr($constraint1, $constraint2);
    }

    /**
     * Performs a logical negation of another constraint.
     *
     * @param Constraint $constraint the constraint to be negated; non-null
     * @return LogicalNot the Not constraint; non-null
     * @api
     */
    public function not(Constraint $constraint)
    {
        return new LogicalNot($constraint);
    }

    /**
     * Filters tuples based on the outcome of a binary operation.
     *
     * @param DynamicOperand $operand1 the first operand; non-null
     * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
     * @param mixed $operand2 the second operand; non-null
     * @return Comparison the constraint; non-null
     * @api
     */
    public function comparison(DynamicOperand $operand1, $operator, $operand2 = null)
    {
        return new Comparison($operand1, $operator, $operand2);
    }

    /**
     * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
     *
     * @param string $propertyName the property name; non-null
     * @param string $selectorName the selector name; non-null
     * @return PropertyValue the operand; non-null
     * @api
     */
    public function propertyValue($propertyName, $selectorName = '')
    {
        return new PropertyValue($propertyName, $selectorName);
    }

    /**
     * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
     *
     * @param DynamicOperand $operand the operand whose value is converted to a lower-case string; non-null
     * @return LowerCase the operand; non-null
     * @api
     */
    public function lowerCase(DynamicOperand $operand)
    {
        return new LowerCase($operand);
    }

    /**
     * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
     *
     * @param DynamicOperand $operand the operand whose value is converted to a upper-case string; non-null
     * @return UpperCase the operand; non-null
     * @api
     */
    public function upperCase(DynamicOperand $operand)
    {
        return new UpperCase($operand);
    }
}
