<?php
namespace TYPO3\Fluid\ViewHelpers\Fixtures;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Dummy object to test Viewhelper behavior on objects with and without a __toString method
 */
class UserWithToString extends UserWithoutToString
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
