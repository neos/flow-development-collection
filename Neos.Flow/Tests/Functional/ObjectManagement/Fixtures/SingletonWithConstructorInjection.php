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
 * A singleton class with constructor injection for testing named arguments with DI
 *
 * @Flow\Scope("singleton")
 */
class SingletonWithConstructorInjection
{
    private SingletonClassA $singletonA;
    private ?string $optionalValue;

    public function __construct(
        SingletonClassA $singletonA,
        ?string $optionalValue = null
    ) {
        $this->singletonA = $singletonA;
        $this->optionalValue = $optionalValue;
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }

    public function getOptionalValue(): ?string
    {
        return $this->optionalValue;
    }
}
