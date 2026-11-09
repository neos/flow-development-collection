<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit\FlowQuery\Operations;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\SliceOperation;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * SliceOperation test
 */
final class SliceOperationTest extends UnitTestCase
{
    public static function sliceExamples(): \Iterator
    {
        yield 'no argument' => [['a', 'b', 'c'], [], ['a', 'b', 'c']];
        yield 'empty array' => [[], [1], []];
        yield 'empty array with end' => [[], [1, 5], []];
        yield 'slice in bounds' => [['a', 'b', 'c', 'd'], [1, 3], ['b', 'c']];
        yield 'positive start' => [['a', 'b', 'c', 'd'], [2], ['c', 'd']];
        yield 'negative start' => [['a', 'b', 'c', 'd'], [-1], ['d']];
        yield 'end out of bounds' => [['a', 'b', 'c', 'd'], [3, 10], ['d']];
        yield 'negative start and end' => [['a', 'b', 'c', 'd'], [-3, -1], ['b', 'c']];
    }

    #[DataProvider('sliceExamples')]
    #[Test]
    public function evaluateSetsTheCorrectPartOfTheContextArray($value, $arguments, $expected)
    {
        $flowQuery = new FlowQuery($value);

        $operation = new SliceOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertEquals($expected, $flowQuery->getContext());
    }
}
