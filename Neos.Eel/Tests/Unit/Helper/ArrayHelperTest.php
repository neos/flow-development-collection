<?php
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

/**
 * Tests for ArrayHelper
 */
class ArrayHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    public function concatExamples()
    {
        return [
            'alpha and numeric values' => [
                [['a', 'b', 'c'], [1, 2, 3]],
                ['a', 'b', 'c', 1, 2, 3]
            ],
            'variable arguments' => [
                [['a', 'b', 'c'], [1, 2, 3], [4, 5, 6]],
                ['a', 'b', 'c', 1, 2, 3, 4, 5, 6]
            ],
            'mixed arguments' => [
                [['a', 'b', 'c'], 1, [2, 3]],
                ['a', 'b', 'c', 1, 2, 3]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider concatExamples
     */
    public function concatWorks($arguments, $expected)
    {
        $helper = new ArrayHelper();
        $result = call_user_func_array([$helper, 'concat'], $arguments);
        self::assertEquals($expected, $result);
    }

    public function joinExamples()
    {
        return [
            'words with default separator' => [['a', 'b', 'c'], null, 'a,b,c'],
            'words with custom separator' => [['a', 'b', 'c'], ', ', 'a, b, c'],
            'empty array' => [[], ', ', ''],
        ];
    }

    /**
     * @test
     * @dataProvider joinExamples
     */
    public function joinWorks($array, $separator, $expected)
    {
        $helper = new ArrayHelper();
        if ($separator !== null) {
            $result = $helper->join($array, $separator);
        } else {
            $result = $helper->join($array);
        }
        self::assertEquals($expected, $result);
    }

    public function sliceExamples()
    {
        return [
            'positive begin without end' => [['a', 'b', 'c', 'd', 'e'], 2, null, ['c', 'd', 'e']],
            'negative begin without end' => [['a', 'b', 'c', 'd', 'e'], -2, null, ['d', 'e']],
            'positive begin and end' => [['a', 'b', 'c', 'd', 'e'], 1, 3, ['b', 'c']],
            'positive begin with negative end' => [['a', 'b', 'c', 'd', 'e'], 1, -2, ['b', 'c']],
            'zero begin with negative end' => [['a', 'b', 'c', 'd', 'e'], 0, -1, ['a', 'b', 'c', 'd']],
            'empty array' => [[], 1, -2, []],
        ];
    }

    /**
     * @test
     * @dataProvider sliceExamples
     */
    public function sliceWorks($array, $begin, $end, $expected)
    {
        $helper = new ArrayHelper();
        if ($end !== null) {
            $result = $helper->slice($array, $begin, $end);
        } else {
            $result = $helper->slice($array, $begin);
        }
        self::assertEquals($expected, $result);
    }

    public function reverseExamples()
    {
        return [
            'empty array' => [[], []],
            'numeric indices' => [['a', 'b', 'c'], ['c', 'b', 'a']],
            'string keys' => [['foo' => 'bar', 'bar' => 'baz'], ['bar' => 'baz', 'foo' => 'bar']],
        ];
    }

    /**
     * @test
     * @dataProvider reverseExamples
     */
    public function reverseWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->reverse($array);

        self::assertEquals($expected, $result);
    }

    public function keysExamples()
    {
        return [
            'empty array' => [[], []],
            'numeric indices' => [['a', 'b', 'c'], [0, 1, 2]],
            'string keys' => [['foo' => 'bar', 'bar' => 'baz'], ['foo', 'bar']],
        ];
    }

    /**
     * @test
     * @dataProvider keysExamples
     */
    public function keysWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->keys($array);

        self::assertEquals($expected, $result);
    }

    public function lengthExamples()
    {
        return [
            'empty array' => [[], 0],
            'array with values' => [['a', 'b', 'c'], 3]
        ];
    }

    /**
     * @test
     * @dataProvider lengthExamples
     */
    public function lengthWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->length($array);

        self::assertEquals($expected, $result);
    }

    public function indexOfExamples()
    {
        return [
            'empty array' => [[], 42, null, -1],
            'array with values' => [['a', 'b', 'c', 'b'], 'b', null, 1],
            'with offset' => [['a', 'b', 'c', 'b'], 'b', 2, 3]
        ];
    }

    /**
     * @test
     * @dataProvider indexOfExamples
     */
    public function indexOfWorks($array, $searchElement, $fromIndex, $expected)
    {
        $helper = new ArrayHelper();
        if ($fromIndex !== null) {
            $result = $helper->indexOf($array, $searchElement, $fromIndex);
        } else {
            $result = $helper->indexOf($array, $searchElement);
        }

        self::assertEquals($expected, $result);
    }

    public function isEmptyExamples()
    {
        return [
            'empty array' => [[], true],
            'array with values' => [['a', 'b', 'c'], false]
        ];
    }

    /**
     * @test
     * @dataProvider isEmptyExamples
     */
    public function isEmptyWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->isEmpty($array);

        self::assertEquals($expected, $result);
    }

    public function firstExamples()
    {
        return [
            'empty array' => [[], false],
            'numeric indices' => [['a', 'b', 'c'], 'a'],
            'string keys' => [['foo' => 'bar', 'bar' => 'baz'], 'bar'],
        ];
    }

    /**
     * @test
     * @dataProvider firstExamples
     */
    public function firstWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->first($array);

        self::assertEquals($expected, $result);
    }

    public function lastExamples()
    {
        return [
            'empty array' => [[], false],
            'numeric indices' => [['a', 'b', 'c'], 'c'],
            'string keys' => [['foo' => 'bar', 'bar' => 'baz'], 'baz'],
        ];
    }

    /**
     * @test
     * @dataProvider lastExamples
     */
    public function lastWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->last($array);

        self::assertEquals($expected, $result);
    }

    public function randomExamples()
    {
        return [
            'empty array' => [[], false],
            'numeric indices' => [['a', 'b', 'c'], true],
            'string keys' => [['foo' => 'bar', 'bar' => 'baz'], true],
        ];
    }

    /**
     * @test
     * @dataProvider randomExamples
     */
    public function randomWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->random($array);

        self::assertEquals($expected, in_array($result, $array));
    }

    public function sortExamples()
    {
        return [
            'empty array' => [[], []],
            'numeric indices' => [['z', '7d', 'i', '7', 'm', 8, 3, 'q'], [3, '7', '7d', 8, 'i', 'm', 'q', 'z']],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['k' => 53, 76, '84216', 'bar', 'foo', 'i' => 181.84, 'foo' => 'abc']],
        ];
    }

    /**
     * @test
     * @dataProvider sortExamples
     */
    public function sortWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $sortedArray = $helper->sort($array);
        self::assertEquals($expected, $sortedArray);
    }

    public function shuffleExamples()
    {
        return [
            'empty array' => [[]],
            'numeric indices' => [['z', '7d', 'i', '7', 'm', 8, 3, 'q']],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz']],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53]],
        ];
    }

    /**
     * @test
     * @dataProvider shuffleExamples
     */
    public function shuffleWorks($array)
    {
        $helper = new ArrayHelper();
        $shuffledArray = $helper->shuffle($array);
        self::assertEquals($array, $shuffledArray);
    }

    public function popExamples()
    {
        return [
            'empty array' => [[], []],
            'numeric indices' => [['z', '7d', 'i', '7'], ['z', '7d', 'i']],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['foo' => 'bar', 'baz' => 'foo']],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76]],
        ];
    }

    /**
     * @test
     * @dataProvider popExamples
     */
    public function popWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $poppedArray = $helper->pop($array);
        self::assertEquals($expected, $poppedArray);
    }

    public function pushExamples()
    {
        return [
            'empty array' => [[], 42, 'foo', [42, 'foo']],
            'numeric indices' => [['z', '7d', 'i', '7'], 42, 'foo', ['z', '7d', 'i', '7', 42, 'foo']],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], 42, 'foo', ['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz', 42, 'foo']],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], 42, 'foo', ['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53, 42, 'foo']],
        ];
    }

    /**
     * @test
     * @dataProvider pushExamples
     */
    public function pushWorks($array, $element1, $element2, $expected)
    {
        $helper = new ArrayHelper();
        $pushedArray = $helper->push($array, $element1, $element2);
        self::assertEquals($expected, $pushedArray);
    }

    public function shiftExamples()
    {
        return [
            'empty array' => [[], []],
            'numeric indices' => [['z', '7d', 'i', '7'], ['7d', 'i', '7']],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['baz' => 'foo', 'bar' => 'baz']],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53]],
        ];
    }

    /**
     * @test
     * @dataProvider shiftExamples
     */
    public function shiftWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $shiftedArray = $helper->shift($array);
        self::assertEquals($expected, $shiftedArray);
    }

    public function unshiftExamples()
    {
        return [
            'empty array' => [[], 'abc', 42, [42, 'abc']],
            'numeric indices' => [['z', '7d', 'i', '7'], 'abc', 42, [42, 'abc', 'z', '7d', 'i', '7']],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], 'abc', 42, [42, 'abc', 'foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz']],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], 'abc', 42, [42, 'abc', 'bar', 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53]],
        ];
    }

    /**
     * @test
     * @dataProvider unshiftExamples
     */
    public function unshiftWorks($array, $element1, $element2, $expected)
    {
        $helper = new ArrayHelper();
        $unshiftedArray = $helper->unshift($array, $element1, $element2);
        self::assertEquals($expected, $unshiftedArray);
    }

    public function spliceExamples()
    {
        return [
            'empty array' => [[], [42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'],
            'numeric indices' => [['z', '7d', 'i', '7'], ['z', '7d', 42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'],
            'string keys' => [['foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'], ['foo' => 'bar', 'baz' => 'foo', 42, 'abc', 'Neos'], 2, 2, 42, 'abc', 'Neos'],
            'mixed keys' => [['bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53], ['bar', 'foo', 42, 'abc', 'Neos', '84216', 76, 'k' => 53], 2, 2, 42, 'abc', 'Neos'],
        ];
    }

    /**
     * @test
     * @dataProvider spliceExamples
     */
    public function spliceWorks($array, $expected, $offset, $length, $element1, $element2, $element3)
    {
        $helper = new ArrayHelper();
        $splicedArray = $helper->splice($array, $offset, $length, $element1, $element2, $element3);
        self::assertEquals($expected, $splicedArray);
    }

    /**
     * @test
     */
    public function spliceNoReplacements()
    {
        $helper = new ArrayHelper();
        $splicedArray = $helper->splice([0, 1, 2, 3, 4, 5], 2, 2);
        self::assertEquals([0, 1, 4, 5], $splicedArray);
    }

    public function flipExamples()
    {
        return [
            'array with values' => [['a', 'b', 'c'], ['a' => 0, 'b' => 1, 'c' => 2]],
            'array with key and values' => [['foo' => 'bar', 24 => 42, 'i' => 181, 42 => 'Neos'], ['bar' => 'foo', 42 => 24, 181 => 'i', 'Neos' => 42]]
        ];
    }

    /**
     * @test
     * @dataProvider flipExamples
     */
    public function flipWorks($array, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->flip($array);

        self::assertEquals($expected, $result);
    }

    public function rangeExamples()
    {
        return [
            'array from one to three' => [
                [1, 3],
                [1, 2, 3]
            ],
            'array from one to seven in steps of two' => [
                [1, 7, 2],
                [1, 3, 5, 7]
            ],
            'array of characters' => [
                ['c', 'g'],
                ['c', 'd', 'e', 'f', 'g']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider rangeExamples
     */
    public function rangeWorks($arguments, $expected)
    {
        $helper = new ArrayHelper();
        $result = call_user_func_array([$helper, 'range'], $arguments);
        self::assertEquals($expected, $result);
    }


    public function setExamples()
    {
        return [
            'add key in empty array' => [
                [[], 'foo', 'bar'],
                ['foo' => 'bar']
            ],
            'add key to array' => [
                [['bar' => 'baz'], 'foo', 'bar'],
                ['bar' => 'baz', 'foo' => 'bar']
            ],
            'override value in array' => [
                [['foo' => 'bar'], 'foo', 'baz'],
                ['foo' => 'baz']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider setExamples
     */
    public function setWorks($arguments, $expected)
    {
        $helper = new ArrayHelper();
        $result = call_user_func_array([$helper, 'set'], $arguments);
        self::assertEquals($expected, $result);
    }

    public function mapExamples()
    {
        return [
            'map squares' => [
                [1, 2, 3, 4],
                function ($x) {
                    return $x * $x;
                },
                [1, 4, 9, 16],
            ],
            'preserve keys' => [
                ['a' => 1, 'b' => 2],
                function ($x) {
                    return $x * 2;
                },
                ['a' => 2, 'b' => 4],
            ],
            'with keys' => [
                [1, 2, 3, 4],
                function ($x, $index) {
                    return $x * $index;
                },
                [0, 2, 6, 12],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mapExamples
     */
    public function mapWorks($array, $callback, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->map($array, $callback);
        self::assertSame($expected, $result);
    }

    public function reduceExamples()
    {
        return [
            'sum with initial value' => [
                [1, 2, 3, 4],
                function ($sum, $x) {
                    return $sum + $x;
                },
                0,
                10,
            ],
            'sum without initial value' => [
                [1, 2, 3, 4],
                function ($sum, $x) {
                    return $sum + $x;
                },
                null,
                10,
            ],
            'sum with empty array and initial value' => [
                [],
                function ($sum, $x) {
                    return $sum + $x;
                },
                0,
                0,
            ],
            'sum with empty array and without initial value' => [
                [],
                function ($sum, $x) {
                    return $sum + $x;
                },
                null,
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider reduceExamples
     */
    public function reduceWorks($array, $callback, $initialValue, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->reduce($array, $callback, $initialValue);
        self::assertSame($expected, $result);
    }

    public function filterExamples()
    {
        return [
            'test by value' => [
                range(0, 5),
                function ($x) {
                    return $x % 2 === 0;
                },
                [
                    0 => 0,
                    2 => 2,
                    4 => 4,
                ],
            ],
            'test element by index' => [
                ['a', 'b', 'c', 'd'],
                function ($x, $index) {
                    return $index % 2 === 0;
                },
                [
                    0 => 'a',
                    2 => 'c',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterExamples
     */
    public function filterWorks($array, $callback, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->filter($array, $callback);
        self::assertSame($expected, $result);
    }

    public function someExamples()
    {
        $isLongWord = function ($x) {
            return strlen($x) >= 8;
        };
        $isFiveApples = function ($x, $key) {
            return $key === 'apple' && $x > 5;
        };
        return [
            'test by value: success' => [
                ['brown', 'elephant', 'dung'],
                $isLongWord,
                true,
            ],
            'test by value: fail' => [
                ['foo', 'bar', 'baz'],
                $isLongWord,
                false,
            ],
            'test by key: success' => [
                ['apple' => 7, 'pear' => 5, 'banana' => 3],
                $isFiveApples,
                true,
            ],
            'test by key: fail' => [
                ['apple' => 3, 'pear' => 5, 'banana' => 7],
                $isFiveApples,
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider someExamples
     */
    public function someWorks($array, $callback, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->some($array, $callback);
        self::assertSame($expected, $result);
    }

    public function everyExamples()
    {
        $isMediumWord = function ($x) {
            return strlen($x) >= 4;
        };
        $isValueEqualIndex = function ($x, $key) {
            return $key === $x;
        };
        return [
            'test by value: success' => [
                ['brown', 'elephant', 'dung'],
                $isMediumWord,
                true,
            ],
            'test by value: fail' => [
                ['foo', 'bar', 'baz'],
                $isMediumWord,
                false,
            ],
            'test by key: success' => [
                [0, 1, 2, 3],
                $isValueEqualIndex,
                true,
            ],
            'test by key: fail' => [
                [0 => 1, 1 => 2, 2 => 3],
                $isValueEqualIndex,
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider everyExamples
     */
    public function everyWorks($array, $callback, $expected)
    {
        $helper = new ArrayHelper();
        $result = $helper->every($array, $callback);
        self::assertSame($expected, $result);
    }
}
