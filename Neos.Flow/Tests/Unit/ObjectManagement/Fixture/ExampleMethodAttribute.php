<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

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
 * A fixture attribute which can be attached to methods.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ExampleMethodAttribute
{
    public function __construct(public readonly string $label = 'default')
    {
    }
}
