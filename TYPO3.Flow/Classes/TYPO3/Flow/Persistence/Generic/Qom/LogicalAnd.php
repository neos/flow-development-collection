<?php
namespace TYPO3\Flow\Persistence\Generic\Qom;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Performs a logical conjunction of two other constraints.
 *
 * To satisfy the And constraint, a tuple must satisfy both constraint1 and
 * constraint2.
 *
 * @api
 */
class LogicalAnd extends \TYPO3\Flow\Persistence\Generic\Qom\Constraint
{
    /**
     * @var \TYPO3\Flow\Persistence\Generic\Qom\Constraint
     */
    protected $constraint1;

    /**
     * @var \TYPO3\Flow\Persistence\Generic\Qom\Constraint
     */
    protected $constraint2;

    /**
     *
     * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint1
     * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint2
     */
    public function __construct(\TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint1, \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint2)
    {
        $this->constraint1 = $constraint1;
        $this->constraint2 = $constraint2;
    }

    /**
     * Gets the first constraint.
     *
     * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint the constraint; non-null
     * @api
     */
    public function getConstraint1()
    {
        return $this->constraint1;
    }

    /**
     * Gets the second constraint.
     *
     * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint the constraint; non-null
     * @api
     */
    public function getConstraint2()
    {
        return $this->constraint2;
    }
}
