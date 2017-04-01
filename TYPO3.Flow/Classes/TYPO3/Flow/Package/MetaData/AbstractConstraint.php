<?php
namespace TYPO3\Flow\Package\MetaData;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
