<?php
namespace TYPO3\Fluid;

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
 * Settings class which is a holder for constants specific to Fluid on v5 and v4.
 *
 */
class Fluid
{
    /**
     * Can be used to enable the verbose mode of Fluid.
     *
     * This enables the following things:
     * - ViewHelper argument descriptions are being parsed from the PHPDoc
     *
     * This is NO PUBLIC API and the way this mode is enabled might change without
     * notice in the future.
     *
     * @var boolean
     */
    public static $debugMode = false;
}
