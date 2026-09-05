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

use Neos\Flow\Annotations as Flow;

/**
 * A singleton which depends on another singleton via property injection, which in turn
 * depends on this one via constructor injection
 */
#[Flow\Scope('singleton')]
class CircularSingletonC
{
    #[Flow\Inject]
    public CircularSingletonD $singletonD;
}
