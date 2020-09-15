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
 * Evaluates to the lower-case string value (or values, if multi-valued) of
 * operand.
 *
 * If operand does not evaluate to a string value, its value is first converted
 * to a string.
 *
 * If operand evaluates to null, the LowerCase operand also evaluates to null.
 *
 * @api
 */
class LowerCase extends DynamicOperand
{
    /**
     * @var DynamicOperand
     */
    protected $operand;

    /**
     * Constructs this LowerCase instance
     *
     * @param DynamicOperand $operand
     */
    public function __construct(DynamicOperand $operand)
    {
        $this->operand = $operand;
    }

    /**
     * Gets the operand whose value is converted to a lower-case string.
     *
     * @return DynamicOperand the operand; non-null
     * @api
     */
    public function getOperand()
    {
        return $this->operand;
    }
}
