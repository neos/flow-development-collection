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

/**
 * A target class for testing advices on methods with variadic parameters
 */
class TargetClassWithVariadicParameters
{
    /**
     * The method arguments the before advice was invoked with
     *
     * @var array<string, mixed>
     */
    public array $beforeAdviceArguments = [];

    /**
     * The method arguments the around advice was invoked with
     *
     * @var array<string, mixed>
     */
    public array $aroundAdviceArguments = [];

    /**
     * A method with a single, typed variadic parameter
     */
    public function sum(int ...$numbers): int
    {
        return array_sum($numbers);
    }

    /**
     * A method with a regular parameter followed by a variadic one
     */
    public function concatenate(string $separator, string ...$parts): string
    {
        return implode($separator, $parts);
    }

    /**
     * A method with an untyped variadic parameter
     */
    public function countItems(...$items): int
    {
        return count($items);
    }
}
