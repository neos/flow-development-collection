<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Proxy of the implementation of dummy interface number 1 for the Reflection tests
 *
 */
class ProxyOfImplementationOfDummyInterface1 extends ImplementationOfDummyInterface1 implements \TYPO3\Flow\Object\Proxy\ProxyInterface
{
    /**
     * A stub to satisfy the Flow Proxy Interface
     */
    public function __wakeup()
    {
    }
}
