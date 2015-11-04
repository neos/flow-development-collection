<?php
namespace TYPO3\Eel\Helper;

/*
 * This file is part of the TYPO3.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * JSON helpers for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class JsonHelper implements ProtectedContextAwareInterface
{
    /**
     * JSON encode the given value
     *
     * @param mixed $value
     * @return string
     */
    public function stringify($value)
    {
        return json_encode($value);
    }

    /**
     * JSON decode the given string
     *
     * @param string $json
     * @param boolean $associativeArrays
     * @return mixed
     */
    public function parse($json, $associativeArrays = true)
    {
        return json_decode($json, $associativeArrays);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
