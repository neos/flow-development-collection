<?php
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
use Neos\Eel\FlowQuery\Operations\UniqueOperation;
use Neos\Eel\Tests\Unit\Fixtures\TestArrayIterator;
use Neos\Flow\Tests\UnitTestCase;

/**
 * UniqueOperation test
 */
class UniqueOperationTest extends UnitTestCase
{
    public function uniqueExamples(): \Generator
    {
        yield 'numeric indices' => [
            ['bar', 12, 'two', 'bar', 13, 12, false, 0, null],
            [0 => 'bar', 1 => 12, 2 => 'two', 4 => 13, 6 => false, 7 => 0]
        ];
        yield 'string keys' => [
            ['foo' => 'bar', 'baz' => 'foo', 'foo' => 'bar2', 'bar' => false, 'foonull' => null],
            ['foo' => 'bar2', 'baz' => 'foo', 'bar' => false]
        ];
        yield 'mixed keys' => [
            ['bar', '24' => 'bar', 'i' => 181.84, 'foo' => 'abc', 'foo2' => 'abc', 76],
            [0 => 'bar', 'i' => 181.84, 'foo' => 'abc', 25 => 76]
        ];
        yield 'traversable' => [
            TestArrayIterator::fromArray(['a', 'a', 'b']),
            [0 => 'a', 2 => 'b']
        ];
    }

    /**
     * @test
     * @dataProvider uniqueExamples
     */
    public function uniqueRemovesDuplicateItemsWorks($array, $expected): void
    {
        $flowQuery = new FlowQuery($array);
        $operation = new UniqueOperation();
        $operation->evaluate($flowQuery, []);

        self::assertEquals($expected, $flowQuery->getContext());
    }
}
