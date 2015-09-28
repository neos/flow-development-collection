<?php
namespace TYPO3\Flow\Persistence\Generic\Qom;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the tuple must not satisfy $constraint.
 *
 * @api
 */
class LogicalNot extends \TYPO3\Flow\Persistence\Generic\Qom\Constraint
{
    /**
     * @var \TYPO3\Flow\Persistence\Generic\Qom\Constraint
     */
    protected $constraint;

    /**
     *
     * @param \TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint
     */
    public function __construct(\TYPO3\Flow\Persistence\Generic\Qom\Constraint $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * Gets the constraint negated by this Not constraint.
     *
     * @return \TYPO3\Flow\Persistence\Generic\Qom\Constraint the constraint; non-null
     * @api
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}
