<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\Helper\ArrayHelper;
use Neos\Eel\Tests\Unit\Fixtures\TestArrayIterator;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for ArrayHelper
 */
final class ArrayHelperTest extends UnitTestCase
{
    public static function concatExamples(): \Iterator
    {
        yield 'alpha and numeric values' => [
            [['a', 'b', 'c'], [1, 2, 3]],
            ['a', 'b', 'c', 1, 2, 3]
        ];
        yield 'variable arguments' => [
            [['a', 'b', 'c'], [1, 2, 3], [4, 5, 6]],
            ['a', 'b', 'c', 1, 2, 3, 4, 5, 6]
        ];
        yield 'mixed arguments' => [
            [['a', 'b', 'c'], 1, [2, 3]],
            ['a', 'b', 'c', 1, 2, 3]
        ];
        yield 'traversable' => [
            [TestArrayIterator::fromArray([1, 2, 3]), [4, 5, 6]],
            [1, 2, 3, 4, 5, 6]
        ];
    }

    #[DataProvider('concatExamples')]
    #[Test]
    public function concatWorks($arguments, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->concat(...$arguments);
        self::assertEquals($expected, $result);
    }

    public static function joinExamples(): \Iterator
    {
        yield 'words with default separator' => [['a', 'b', 'c'], null, 'a,b,c'];
        yield 'words with custom separator' => [['a', 'b', 'c'], ', ', 'a, b, c'];
        yield 'empty array' => [[], ', ', ''];
        yield 'traversable' => [TestArrayIterator::fromArray(['a', 'b', 'c']), ', ', 'a, b, c'];
    }

    #[DataProvider('joinExamples')]
    #[Test]
    public function joinWorks($array, $separator, $expected): void
    {
        $helper = new ArrayHelper();
        if ($separator !== null) {
            $result = $helper->join($array, $separator);
        } else {
            $result = $helper->join($array);
        }
        self::assertEquals($expected, $result);
    }

    public static function sliceExamples(): \Iterator
    {
        yield 'positive begin without end' => [['a', 'b', 'c', 'd', 'e'], 2, null, ['c', 'd', 'e']];
        yield 'negative begin without end' => [['a', 'b', 'c', 'd', 'e'], -2, null, ['d', 'e']];
        yield 'positive begin and end' => [['a', 'b', 'c', 'd', 'e'], 1, 3, ['b', 'c']];
        yield 'positive begin with negative end' => [['a', 'b', 'c', 'd', 'e'], 1, -2, ['b', 'c']];
        yield 'zero begin with negative end' => [['a', 'b', 'c', 'd', 'e'], 0, -1, ['a', 'b', 'c', 'd']];
        yield 'empty array' => [[], 1, -2, []];
        yield 'traversable' => [TestArrayIterator::fromArray(['a', 'b', 'c']), 2, null, ['c']];
    }

    #[DataProvider('sliceExamples')]
    #[Test]
    public function sliceWorks($array, $begin, $end, $expected): void
    {
        $helper = new ArrayHelper();
        if ($end !== null) {
            $result = $helper->slice($array, $begin, $end);
        } else {
            $result = $helper->slice($array, $begin);
        }
        self::assertEquals($expected, $result);
    }

    public static function reverseExamples(): \Iterator
    {
        yield 'empty array' => [[], []];
        yield 'numeric indices' => [['a', 'b', 'c'], ['c', 'b', 'a']];
        yield 'string keys' => [['foo' => 'bar', 'bar' => 'baz'], ['bar' => 'baz', 'foo' => 'bar']];
        yield 'traversable' => [TestArrayIterator::fromArray(['a' => 1, 'b' => 2, 'c' => 3]), ['c' => 3, 'b' => 2, 'a' => 1]];
    }

    #[DataProvider('reverseExamples')]
    #[Test]
    public function reverseWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->reverse($array);

        self::assertEquals($expected, $result);
    }

    public static function keysExamples(): \Iterator
    {
        yield 'empty array' => [[], []];
        yield 'numeric indices' => [['a', 'b', 'c'], [0, 1, 2]];
        yield 'string keys' => [['foo' => 'bar', 'bar' => 'baz'], ['foo', 'bar']];
        yield 'traversable' => [TestArrayIterator::fromArray(['foo' => 'bar', 'bar' => 'baz']), ['foo', 'bar']];
    }

    #[DataProvider('keysExamples')]
    #[Test]
    public function keysWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->keys($array);

        self::assertEquals($expected, $result);
    }

    public static function valuesExamples(): \Iterator
    {
        yield 'empty array' => [[], []];
        yield 'numeric indices' => [[0 => 'a', 2 => 'b', 3 => 'c'], ['a', 'b', 'c']];
        yield 'string keys' => [['foo' => 'bar', 'bar' => 'baz'], ['bar', 'baz']];
        yield 'traversable' => [TestArrayIterator::fromArray(['foo' => 'bar', 'bar' => 'baz']), ['bar', 'baz']];
    }

    /**
     * @param array $array
     * @param array $expected
     */
    #[DataProvider('valuesExamples')]
    #[Test]
    public function valuesWorks($array, array $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->values($array);

        self::assertEquals($expected, $result);
    }

    public static function lengthExamples(): \Iterator
    {
        yield 'empty array' => [[], 0];
        yield 'array with values' => [['a', 'b', 'c'], 3];
        yield 'traversable' => [TestArrayIterator::fromArray(['a', 'b', 'c']), 3];
    }

    #[DataProvider('lengthExamples')]
    #[Test]
    public function lengthWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->length($array);

        self::assertEquals($expected, $result);
    }

    public static function indexOfExamples(): \Iterator
    {
        yield 'empty array' => [[], 42, null, -1];
        yield 'array with values' => [['a', 'b', 'c', 'b'], 'b', null, 1];
        yield 'with offset' => [['a', 'b', 'c', 'b'], 'b', 2, 3];
        yield 'associative' => [['a' => 'el1', 'b' => 'el2'], 'el2', null, 1];
        yield 'associative with offset' => [['a' => 'el1', 'b' => 'el2'], 'el2', 1, 1];
        yield 'traversable' => [TestArrayIterator::fromArray(['a', 'b', 'c', 'b']), 'b', null, 1];
    }

    #[DataProvider('indexOfExamples')]
    #[Test]
    public function indexOfWorks($array, $searchElement, $fromIndex, $expected): void
    {
        $helper = new ArrayHelper();
        if ($fromIndex !== null) {
            $result = $helper->indexOf($array, $searchElement, $fromIndex);
        } else {
            $result = $helper->indexOf($array, $searchElement);
        }

        self::assertEquals($expected, $result);
    }

    public static function isEmptyExamples(): \Iterator
    {
        yield 'empty array' => [[], true];
        yield 'array with values' => [['a', 'b', 'c'], false];
        yield 'traversable' => [TestArrayIterator::fromArray(['a', 'b', 'c']), false];
    }

    #[DataProvider('isEmptyExamples')]
    #[Test]
    public function isEmptyWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->isEmpty($array);

        self::assertEquals($expected, $result);
    }

    public static function firstExamples(): \Iterator
    {
        yield 'empty array' => [[], false];
        yield 'numeric indices' => [['a', 'b', 'c'], 'a'];
        yield 'string keys' => [['foo' => 'bar', 'bar' => 'baz'], 'bar'];
        yield 'traversable' => [TestArrayIterator::fromArray(['foo' => 'bar', 'bar' => 'baz']), 'bar'];
        yield 'empty traversable' => [TestArrayIterator::fromArray([]), false];
    }

    #[DataProvider('firstExamples')]
    #[Test]
    public function firstWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->first($array);

        self::assertEquals($expected, $result);
    }

    public static function lastExamples(): \Iterator
    {
        yield 'empty array' => [[], false];
        yield 'numeric indices' => [['a', 'b', 'c'], 'c'];
        yield 'string keys' => [['foo' => 'bar', 'bar' => 'baz'], 'baz'];
        yield 'traversable' => [TestArrayIterator::fromArray(['foo' => 'bar', 'bar' => 'baz']), 'baz'];
        yield 'empty traversable' => [TestArrayIterator::fromArray([]), false];
    }

    #[DataProvider('lastExamples')]
    #[Test]
    public function lastWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->last($array);

        self::assertEquals($expected, $result);
    }

    public static function randomExamples(): \Iterator
    {
        yield 'empty array' => [[], false];
        yield 'numeric indices' => [['a', 'b', 'c'], true];
        yield 'string keys' => [['foo' => 'bar', 'bar' => 'baz'], true];
        yield 'traversable' => [TestArrayIterator::fromArray(['foo' => 'bar', 'bar' => 'baz']), true];
    }

    #[DataProvider('randomExamples')]
    #[Test]
    public function randomWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->random($array);

        if ($array instanceof \Traversable) {
            $array = iterator_to_array($array);
        }

        self::assertEquals($expected, in_array($result, $array));
    }

    public static function sortExamples(): \Iterator
    {
        yield 'empty array' => [[], []];
        yield 'numeric indices' => [['z', '7d', 'i', '7', 'm', 8, 3, 'q'], [3, '7', '7d', 8, 'i', 'm', 'q', 'z']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['k' => 53, 76, '84216', 'bar', 'foo', 'i' => 181.84, 'foo' => 'abc']];
        yield 'traversable' => [TestArrayIterator::fromArray([4, 2, 3, 1]), [1, 2, 3, 4]];
    }

    #[DataProvider('sortExamples')]
    #[Test]
    public function sortWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $sortedArray = $helper->sort($array);
        self::assertEquals($expected, $sortedArray);
    }

    public static function ksortExamples(): \Iterator
    {
        yield 'no keys' => [['z', '7d', 'i', '7', 'm', 8, 3, 'q'], ['z', '7d', 'i', '7', 'm', 8, 3, 'q']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['bar' => 'baz', 'baz' => 'foo', 'foo' => 'bar']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['0' => 'bar', '24' => 'foo', '25' => '84216', '26' => 76, 'foo' => 'abc', 'i' => 181.84, 'k' => 53]];
        yield 'traversable' => [TestArrayIterator::fromArray(['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz']), ['bar' => 'baz', 'baz' => 'foo', 'foo' => 'bar']];
    }

    #[DataProvider('ksortExamples')]
    #[Test]
    public function ksortWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $sortedArray = $helper->ksort($array);
        self::assertEquals($expected, $sortedArray);
    }

    public static function shuffleExamples(): \Iterator
    {
        yield 'empty array' => [[]];
        yield 'numeric indices' => [['z', '7d', 'i', '7', 'm', 8, 3, 'q']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53]];
        yield 'traversable' => [TestArrayIterator::fromArray([1, 2, 3, 4])];
    }

    #[DataProvider('shuffleExamples')]
    #[Test]
    public function shuffleWorks($array): void
    {
        $helper = new ArrayHelper();
        $shuffledArray = $helper->shuffle($array);

        if ($array instanceof \Traversable) {
            $array = iterator_to_array($array);
        }

        self::assertEquals($array, $shuffledArray);
    }

    public static function uniqueExamples(): \Iterator
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

    #[DataProvider('uniqueExamples')]
    #[Test]
    public function uniqueWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $uniqueddArray = $helper->unique($array);
        self::assertEquals($expected, $uniqueddArray);
    }

    public static function popExamples(): \Iterator
    {
        yield 'empty array' => [[], []];
        yield 'numeric indices' => [['z', '7d', 'i', '7'], ['z', '7d', 'i']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['foo' => 'bar', 'baz' => 'foo']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76]];
        yield 'traversable' => [TestArrayIterator::fromArray(['z', '7d', 'i', '7']), ['z', '7d', 'i']];
    }

    #[DataProvider('popExamples')]
    #[Test]
    public function popWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $poppedArray = $helper->pop($array);
        self::assertEquals($expected, $poppedArray);
    }

    public static function pushExamples(): \Iterator
    {
        yield 'empty array' => [[], 42, 'foo', [42, 'foo']];
        yield 'numeric indices' => [['z', '7d', 'i', '7'], 42, 'foo', ['z', '7d', 'i', '7', 42, 'foo']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], 42, 'foo', ['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz', 42, 'foo']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], 42, 'foo', ['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53, 42, 'foo']];
        yield 'traversable' => [TestArrayIterator::fromArray(['a']), 'b', 'c', ['a', 'b', 'c']];
        # expect cast scalar (as arg $array) to array
        yield 'string' => ['a', 'b', 'c', ['a', 'b', 'c']];
        yield 'int' => [123, 'b', 'c', [123, 'b', 'c']];
        # ignore null (as arg $array)
        yield 'null' => [null, 'b', 'c', ['b', 'c']];
    }

    #[DataProvider('pushExamples')]
    #[Test]
    public function pushWorks($array, $element1, $element2, $expected): void
    {
        $helper = new ArrayHelper();
        $pushedArray = $helper->push($array, $element1, $element2);
        self::assertEquals($expected, $pushedArray);
    }

    public static function shiftExamples(): \Iterator
    {
        yield 'empty array' => [[], []];
        yield 'numeric indices' => [['z', '7d', 'i', '7'], ['7d', 'i', '7']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['baz' => 'foo', 'bar' => 'baz']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53]];
        yield 'traversable' => [TestArrayIterator::fromArray(['z', '7d', 'i', '7']), ['7d', 'i', '7']];
    }

    #[DataProvider('shiftExamples')]
    #[Test]
    public function shiftWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $shiftedArray = $helper->shift($array);
        self::assertEquals($expected, $shiftedArray);
    }

    public static function unshiftExamples(): \Iterator
    {
        yield 'empty array' => [[], 'abc', 42, [42, 'abc']];
        yield 'numeric indices' => [['z', '7d', 'i', '7'], 'abc', 42, [42, 'abc', 'z', '7d', 'i', '7']];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], 'abc', 42, [42, 'abc', 'foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz']];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], 'abc', 42, [42, 'abc', 'bar', 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53]];
        yield 'traversable' => [TestArrayIterator::fromArray(['z', '7d', 'i', '7']), 'a', 42, [42, 'a', 'z', '7d', 'i', '7']];
    }

    #[DataProvider('unshiftExamples')]
    #[Test]
    public function unshiftWorks($array, $element1, $element2, $expected): void
    {
        $helper = new ArrayHelper();
        $unshiftedArray = $helper->unshift($array, $element1, $element2);
        self::assertEquals($expected, $unshiftedArray);
    }

    public static function spliceExamples(): \Iterator
    {
        yield 'empty array' => [[], [42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'];
        yield 'numeric indices' => [['z', '7d', 'i', '7'], ['z', '7d', 42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'];
        yield 'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['foo' => 'bar', 'baz' => 'foo', 42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'];
        yield 'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['bar', 'foo', 42, 'abc', 'Neos', '84216', 76, 'k' => 53], 2, 2, 42, 'abc', 'Neos'];
        yield 'traversable' => [TestArrayIterator::fromArray(['z', '7d', 'i', '7']), ['z', '7d', 42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'];
    }

    #[DataProvider('spliceExamples')]
    #[Test]
    public function spliceWorks($array, $expected, $offset, $length, $element1, $element2, $element3): void
    {
        $helper = new ArrayHelper();
        $splicedArray = $helper->splice($array, $offset, $length, $element1, $element2, $element3);
        self::assertEquals($expected, $splicedArray);
    }

    #[Test]
    public function spliceNoReplacements(): void
    {
        $helper = new ArrayHelper();
        $splicedArray = $helper->splice([0, 1, 2, 3, 4, 5], 2, 2);
        self::assertSame([0, 1, 4, 5], $splicedArray);
    }

    public static function flipExamples(): \Iterator
    {
        yield 'array with values' => [['a', 'b', 'c'], ['a' => 0, 'b' => 1, 'c' => 2]];
        yield 'array with key and values' => [['foo' => 'bar', 24 => 42, 'i' => 181, 42 => 'Neos'], ['bar' => 'foo', 42 => 24, 181 => 'i', 'Neos' => 42]];
        yield 'traversable' => [TestArrayIterator::fromArray(['a', 'b', 'c']), ['a' => 0, 'b' => 1, 'c' => 2]];
    }

    #[DataProvider('flipExamples')]
    #[Test]
    public function flipWorks($array, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->flip($array);

        self::assertEquals($expected, $result);
    }

    public static function rangeExamples(): \Iterator
    {
        yield 'array from one to three' => [
            [1, 3],
            [1, 2, 3]
        ];
        yield 'array from one to seven in steps of two' => [
            [1, 7, 2],
            [1, 3, 5, 7]
        ];
        yield 'array of characters' => [
            ['c', 'g'],
            ['c', 'd', 'e', 'f', 'g']
        ];
    }

    #[DataProvider('rangeExamples')]
    #[Test]
    public function rangeWorks($arguments, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->range(...$arguments);
        self::assertEquals($expected, $result);
    }


    public static function setExamples(): \Iterator
    {
        yield 'add key in empty array' => [
            [[], 'foo', 'bar'],
            ['foo' => 'bar']
        ];
        yield 'add key to array' => [
            [['bar' => 'baz'], 'foo', 'bar'],
            ['bar' => 'baz', 'foo' => 'bar']
        ];
        yield 'override value in array' => [
            [['foo' => 'bar'], 'foo', 'baz'],
            ['foo' => 'baz']
        ];
        yield 'traversable' => [
            [TestArrayIterator::fromArray(['bar' => 'baz']), 'foo', 'bar'],
            ['bar' => 'baz', 'foo' => 'bar']
        ];
    }

    #[DataProvider('setExamples')]
    #[Test]
    public function setWorks($arguments, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->set(...$arguments);
        self::assertEquals($expected, $result);
    }

    public static function mapExamples(): \Iterator
    {
        yield 'map squares' => [
            [1, 2, 3, 4],
            function ($x) {
                return $x * $x;
            },
            [1, 4, 9, 16],
        ];
        yield 'preserve keys' => [
            ['a' => 1, 'b' => 2],
            function ($x) {
                return $x * 2;
            },
            ['a' => 2, 'b' => 4],
        ];
        yield 'with keys' => [
            [1, 2, 3, 4],
            function ($x, $index) {
                return $x * $index;
            },
            [0, 2, 6, 12],
        ];
        yield 'traversable' => [
            TestArrayIterator::fromArray([1, 2, 3, 4]),
            function ($x) {
                return $x * $x;
            },
            [1, 4, 9, 16],
        ];
    }

    #[DataProvider('mapExamples')]
    #[Test]
    public function mapWorks($array, $callback, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->map($array, $callback);
        self::assertSame($expected, $result);
    }

    public static function reduceExamples(): \Iterator
    {
        yield 'sum with initial value' => [
            [1, 2, 3, 4],
            function ($sum, $x) {
                return $sum + $x;
            },
            0,
            10,
        ];
        yield 'sum without initial value' => [
            [1, 2, 3, 4],
            function ($sum, $x) {
                return $sum + $x;
            },
            null,
            10,
        ];
        yield 'sum with empty array and initial value' => [
            [],
            function ($sum, $x) {
                return $sum + $x;
            },
            0,
            0,
        ];
        yield 'sum with empty array and without initial value' => [
            [],
            function ($sum, $x) {
                return $sum + $x;
            },
            null,
            null,
        ];
        yield 'traversable' => [
            TestArrayIterator::fromArray([1, 2, 3, 4]),
            function ($sum, $x) {
                return $sum + $x;
            },
            0,
            10,
        ];
        yield 'traversable without initial value' => [
            TestArrayIterator::fromArray([1, 2, 3, 4]),
            function ($sum, $x) {
                return $sum + $x;
            },
            null,
            10,
        ];
    }

    #[DataProvider('reduceExamples')]
    #[Test]
    public function reduceWorks($array, $callback, $initialValue, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->reduce($array, $callback, $initialValue);
        self::assertSame($expected, $result);
    }

    public static function filterExamples(): \Iterator
    {
        yield 'test by value' => [
            range(0, 5),
            function ($x) {
                return $x % 2 === 0;
            },
            [
                0 => 0,
                2 => 2,
                4 => 4,
            ],
        ];
        yield 'test element by index' => [
            ['a', 'b', 'c', 'd'],
            function ($x, $index) {
                return $index % 2 === 0;
            },
            [
                0 => 'a',
                2 => 'c',
            ],
        ];
        yield 'test with empty filter function' => [
            [1,null,2,null,3],
            null,
            [
                0 => 1,
                2 => 2,
                4 => 3,
            ],
        ];
        yield 'traversable' => [
            TestArrayIterator::fromArray([0, 1, 2, 3, 4, 5]),
            function ($x) {
                return $x % 2 === 0;
            },
            [
                0 => 0,
                2 => 2,
                4 => 4,
            ],
        ];
    }

    #[DataProvider('filterExamples')]
    #[Test]
    public function filterWorks($array, $callback, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->filter($array, $callback);
        self::assertSame($expected, $result);
    }

    public static function someExamples(): \Iterator
    {
        $isLongWord = function ($x) {
            return strlen($x) >= 8;
        };
        $isFiveApples = function ($x, $key) {
            return $key === 'apple' && $x > 5;
        };
        yield 'test by value: success' => [
            ['brown', 'elephant', 'dung'],
            $isLongWord,
            true,
        ];
        yield 'test by value: fail' => [
            ['foo', 'bar', 'baz'],
            $isLongWord,
            false,
        ];
        yield 'test by key: success' => [
            ['apple' => 7, 'pear' => 5, 'banana' => 3],
            $isFiveApples,
            true,
        ];
        yield 'test by key: fail' => [
            ['apple' => 3, 'pear' => 5, 'banana' => 7],
            $isFiveApples,
            false,
        ];
        yield 'traversable' => [
            TestArrayIterator::fromArray(['brown', 'elephant', 'dung']),
            $isLongWord,
            true,
        ];
    }

    #[DataProvider('someExamples')]
    #[Test]
    public function someWorks($array, $callback, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->some($array, $callback);
        self::assertSame($expected, $result);
    }

    public static function everyExamples(): \Iterator
    {
        $isMediumWord = function ($x) {
            return strlen($x) >= 4;
        };
        $isValueEqualIndex = function ($x, $key) {
            return $key === $x;
        };
        yield 'test by value: success' => [
            ['brown', 'elephant', 'dung'],
            $isMediumWord,
            true,
        ];
        yield 'test by value: fail' => [
            ['foo', 'bar', 'baz'],
            $isMediumWord,
            false,
        ];
        yield 'test by key: success' => [
            [0, 1, 2, 3],
            $isValueEqualIndex,
            true,
        ];
        yield 'test by key: fail' => [
            [0 => 1, 1 => 2, 2 => 3],
            $isValueEqualIndex,
            false,
        ];
        yield 'traversable' => [
            TestArrayIterator::fromArray([0 => 1, 1 => 2, 2 => 3]),
            $isValueEqualIndex,
            false,
        ];
    }

    #[DataProvider('everyExamples')]
    #[Test]
    public function everyWorks($array, $callback, $expected): void
    {
        $helper = new ArrayHelper();
        $result = $helper->every($array, $callback);
        self::assertSame($expected, $result);
    }
}
