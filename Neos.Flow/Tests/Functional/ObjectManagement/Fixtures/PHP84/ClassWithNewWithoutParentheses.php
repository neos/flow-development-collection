<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP84;

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
 * A class using "new" without surrounding parentheses, which is a PHP 8.4 feature.
 *
 * Note that "new self()" is rewritten to "new static()" by the proxy compiler, which is why
 * the combination of both features is of particular interest here.
 */
class ClassWithNewWithoutParentheses
{
    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    protected string $value = 'the initial value';

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function describe(): string
    {
        return 'described: ' . $this->value;
    }

    public function describeNewSelf(): string
    {
        return new self()->describe();
    }

    public function describeNewStatic(): string
    {
        return new static()->describe();
    }

    public function describeNewClassName(): string
    {
        return new ClassWithNewWithoutParentheses()->describe();
    }

    public function readValueOfNewSelf(): string
    {
        return new self()->value;
    }

    public function createNewSelf(): object
    {
        return new self();
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
