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
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the tuple must not satisfy $constraint.
 *
 * @api
 */
class LogicalNot extends Constraint
{
    /**
     * @var Constraint
     */
    protected $constraint;

    /**
     *
     * @param Constraint $constraint
     */
    public function __construct(Constraint $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * Gets the constraint negated by this Not constraint.
     *
     * @return Constraint the constraint; non-null
     * @api
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}
