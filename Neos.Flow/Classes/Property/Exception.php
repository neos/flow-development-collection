<?php
namespace Neos\Flow\Property;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An generic Property related exception
 *
 * @api
 */
class Exception extends \Neos\Flow\Exception
{
    /**
     * Return the status code of the nested exception, if any.
     *
     * @return integer
     */
    public function getStatusCode()
    {
        $nestedException = $this->getPrevious();
        if ($nestedException !== null && $nestedException instanceof \Neos\Flow\Exception) {
            return $nestedException->getStatusCode();
        }
        return parent::getStatusCode();
    }
}
