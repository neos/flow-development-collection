<?php
namespace TYPO3\Fluid\ViewHelpers\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
