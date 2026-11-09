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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;

/**
 * A class using dynamic class constant fetch, which is a PHP 8.3 feature
 */
class ClassWithDynamicConstantFetch
{
    public const FIRST = 'the first value';

    public const SECOND = 'the second value';

    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    public function fetchViaSelf(string $constantName): string
    {
        return self::{$constantName};
    }

    public function fetchViaStatic(string $constantName): string
    {
        return static::{$constantName};
    }

    public function fetchViaClassName(string $constantName): string
    {
        return ClassWithDynamicConstantFetch::{$constantName};
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
