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


/**
 * Performs a logical conjunction of two other constraints.
 *
 * To satisfy the And constraint, a tuple must satisfy both constraint1 and
 * constraint2.
 *
 * @api
 */
class LogicalAnd extends Constraint
{
    /**
     * @var Constraint
     */
    protected $constraint1;

    /**
     * @var Constraint
     */
    protected $constraint2;

    /**
     *
     * @param Constraint $constraint1
     * @param Constraint $constraint2
     */
    public function __construct(Constraint $constraint1, Constraint $constraint2)
    {
        $this->constraint1 = $constraint1;
        $this->constraint2 = $constraint2;
    }

    /**
     * Gets the first constraint.
     *
     * @return Constraint the constraint; non-null
     * @api
     */
    public function getConstraint1()
    {
        return $this->constraint1;
    }

    /**
     * Gets the second constraint.
     *
     * @return Constraint the constraint; non-null
     * @api
     */
    public function getConstraint2()
    {
        return $this->constraint2;
    }
}
