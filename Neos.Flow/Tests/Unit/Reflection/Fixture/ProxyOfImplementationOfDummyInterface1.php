<?php
namespace Neos\Flow\Tests\Reflection\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;

/**
 * Proxy of the implementation of dummy interface number 1 for the Reflection tests
 */
class ProxyOfImplementationOfDummyInterface1 extends ImplementationOfDummyInterface1 implements ProxyInterface
{
    /**
     * A stub to satisfy the Flow Proxy Interface
     */
    public function __wakeup()
    {
    }
}
