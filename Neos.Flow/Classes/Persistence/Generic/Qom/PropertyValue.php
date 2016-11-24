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
 * Evaluates to the value (or values, if multi-valued) of a property.
 *
 * If, for a tuple, the selector node does not have a property named property,
 * the operand evaluates to null.
 *
 * The query is invalid if:
 *
 * selector is not the name of a selector in the query, or
 * property is not a syntactically valid property name.
 *
 * @api
 */
class PropertyValue extends DynamicOperand
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * Constructs this PropertyValue instance
     *
     * @param string $propertyName
     * @param string $selectorName
     */
    public function __construct($propertyName, $selectorName = '')
    {
        $this->propertyName = $propertyName;
        $this->selectorName = $selectorName;
    }

    /**
     * Gets the name of the selector against which to evaluate this operand.
     *
     * @return string the selector name; non-null
     * @api
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * Gets the name of the property.
     *
     * @return string the property name; non-null
     * @api
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
