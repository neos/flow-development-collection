<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP83;

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
 * An abstract base class providing a method which can be overridden
 */
abstract class AbstractGreetingProvider implements GreetingProviderInterface
{
    abstract public function greet(): string;

    public function describe(): string
    {
        return 'a greeting provider';
    }
}
