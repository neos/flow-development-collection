<?php
namespace Neos\Flow\Security\Authorization\Privilege\Parameter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
