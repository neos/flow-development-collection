<?php
namespace Neos\Eel;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for classes that are aware of a protected Eel context
 *
 * Allows for custom, dynamic policies for allowed method calls
 */
interface ProtectedContextAwareInterface
{
    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName);
}
