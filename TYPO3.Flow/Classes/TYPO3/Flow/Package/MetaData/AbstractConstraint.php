<?php
namespace TYPO3\Flow\Package\MetaData;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Constraint meta data model
 *
 */
abstract class AbstractConstraint
{
    /**
     * One of depends, conflicts or suggests
     * @var string
     */
    protected $constraintType;

    /**
     * The constraint name or value
     * @var string
     */
    protected $value;

    /**
     * Meta data constraint constructor
     *
     * @param string $constraintType
     * @param string $value
     * @param string $minVersion
     * @param string $maxVersion
     */
    public function __construct($constraintType, $value, $minVersion = null, $maxVersion = null)
    {
        $this->constraintType = $constraintType;
        $this->value = $value;
        $this->minVersion = $minVersion;
        $this->maxVersion = $maxVersion;
    }

    /**
     * @return string The constraint name or value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string The constraint type (depends, conflicts, suggests)
     */
    public function getConstraintType()
    {
        return $this->constraintType;
    }

    /**
     * @return string The constraint scope (package, system)
     */
    abstract public function getConstraintScope();
}
