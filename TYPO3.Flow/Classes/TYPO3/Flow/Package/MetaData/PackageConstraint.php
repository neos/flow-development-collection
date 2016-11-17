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

use TYPO3\Flow\Package\MetaDataInterface;

/**
 * Package constraint meta model
 *
 */
class PackageConstraint extends AbstractConstraint
{
    /**
     * @return string The constraint scope
     * @see Constraint::getConstraintScope()
     */
    public function getConstraintScope()
    {
        return MetaDataInterface::CONSTRAINT_SCOPE_PACKAGE;
    }
}
