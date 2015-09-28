<?php
namespace TYPO3\Flow\Package\MetaData;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Package constraint meta model
 *
 */
class PackageConstraint extends \TYPO3\Flow\Package\MetaData\AbstractConstraint
{
    /**
     * @return string The constraint scope
     * @see \TYPO3\Flow\Package\MetaData\Constraint::getConstraintScope()
     */
    public function getConstraintScope()
    {
        return \TYPO3\Flow\Package\MetaDataInterface::CONSTRAINT_SCOPE_PACKAGE;
    }
}
