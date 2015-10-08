<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Parameter;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A privilege parameter of type string
 */
class StringPrivilegeParameter extends AbstractPrivilegeParameter
{
    /**
     * @return array
     */
    public function getPossibleValues()
    {
        return null;
    }

    /**
     * @param mixed $value
     * @return boolean
     */
    public function validate($value)
    {
        return is_string($value);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'String';
    }
}
