<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
