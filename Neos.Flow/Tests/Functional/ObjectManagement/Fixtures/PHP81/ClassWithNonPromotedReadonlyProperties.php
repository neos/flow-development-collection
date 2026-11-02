<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81;

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
 * A class with readonly properties which are not promoted constructor properties but are
 * assigned in the constructor body.
 */
class ClassWithNonPromotedReadonlyProperties
{
    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    public readonly string $label;

    private readonly int $number;

    public function __construct(string $label = 'the default label', int $number = 42)
    {
        $this->label = $label;
        $this->number = $number * 2;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
