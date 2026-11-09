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
 * A class using the #[\Override] attribute, which is a PHP 8.3 feature
 */
class ClassWithOverrideAttribute extends AbstractGreetingProvider
{
    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    /**
     * Overrides an abstract method declared in the parent class and the interface
     */
    #[\Override]
    public function greet(): string
    {
        return 'Greetings!';
    }

    /**
     * Overrides a concrete method declared in the parent class
     */
    #[\Override]
    public function describe(): string
    {
        return 'a very polite greeting provider';
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
