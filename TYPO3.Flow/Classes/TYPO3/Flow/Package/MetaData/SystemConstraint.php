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
 * System constraint meta model
 *
 */
class SystemConstraint extends \TYPO3\Flow\Package\MetaData\AbstractConstraint
{
    /**
     * The type for a system scope constraint (e.g. "Memory")
     *
     * @var string
     */
    protected $type;

    /**
     * Meta data system constraint constructor
     *
     * @param string $constraintType
     * @param string $type
     * @param string $value
     * @param string $minVersion
     * @param string $maxVersion
     */
    public function __construct($constraintType, $type, $value = null, $minVersion = null, $maxVersion = null)
    {
        if (!strlen($value)) {
            $value = null;
        }
        parent::__construct($constraintType, $value, $minVersion, $maxVersion);
        $this->type = $type;
    }

    /**
     * @return string The system constraint type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string The constraint scope
     * @see \TYPO3\Flow\Package\MetaData\Constraint\getConstraintScope()
     */
    public function getConstraintScope()
    {
        return \TYPO3\Flow\Package\MetaDataInterface::CONSTRAINT_SCOPE_SYSTEM;
    }
}
