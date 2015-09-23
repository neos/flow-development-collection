<?php
namespace TYPO3\Flow\Property;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An generic Property related exception
 *
 * @api
 */
class Exception extends \TYPO3\Flow\Exception
{
    /**
     * Return the status code of the nested exception, if any.
     *
     * @return integer
     */
    public function getStatusCode()
    {
        $nestedException = $this->getPrevious();
        if ($nestedException !== null && $nestedException instanceof \TYPO3\Flow\Exception) {
            return $nestedException->getStatusCode();
        }
        return parent::getStatusCode();
    }
}
