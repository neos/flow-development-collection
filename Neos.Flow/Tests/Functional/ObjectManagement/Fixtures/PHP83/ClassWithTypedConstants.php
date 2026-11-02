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
 * A class with typed class constants, which is a PHP 8.3 feature
 */
class ClassWithTypedConstants
{
    public const int ANSWER = 42;

    public const string GREETING = 'Hello';

    protected const array NUMBERS = [1, 2, 3];

    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    public function getAnswer(): int
    {
        return self::ANSWER;
    }

    public function getGreeting(): string
    {
        return static::GREETING;
    }

    /**
     * @return array<int, int>
     */
    public function getNumbers(): array
    {
        return ClassWithTypedConstants::NUMBERS;
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
