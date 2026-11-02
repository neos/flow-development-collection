<?php

namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Generator;

/**
 * A target class with generator methods for testing the AOP framework
 */
class TargetClassWithGeneratorMethods
{
    public bool $beforeAdviceWasInvoked = false;

    public bool $aroundAdviceWasInvoked = false;

    /**
     * A generator method which is advised by a before advice
     *
     * @return Generator<int, string>
     */
    public function generateGreetings(string $name): Generator
    {
        yield 'Hello, ' . $name;
        yield 'Hi, ' . $name;
        yield 'Hey, ' . $name;
    }

    /**
     * A generator method which is advised by an around advice
     *
     * @return Generator<int, int>
     */
    public function generateNumbers(int $upperBound): Generator
    {
        for ($number = 1; $number <= $upperBound; $number++) {
            yield $number;
        }
    }
}
