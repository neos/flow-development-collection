<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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
 * A prototype which gets another prototype injected through its constructor, which in turn
 * depends on this class through property injection
 */
class CircularPrototypeE
{
    public function __construct(public CircularPrototypeF $prototypeF)
    {
    }
}
