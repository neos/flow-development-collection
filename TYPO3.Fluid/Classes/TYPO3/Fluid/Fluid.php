<?php
namespace TYPO3\Fluid;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
