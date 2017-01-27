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
        $this->assertEquals($expected, $result);
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
        $this->assertEquals($expected, $result);
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
        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, in_array($result, $array));
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
        $this->assertEquals($expected, $sortedArray);
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
        $this->assertEquals($array, $shuffledArray);
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
        $this->assertEquals($expected, $poppedArray);
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
        $this->assertEquals($expected, $pushedArray);
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
        $this->assertEquals($expected, $shiftedArray);
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
        $this->assertEquals($expected, $unshiftedArray);
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
        $this->assertEquals($expected, $splicedArray);
    }

    /**
     * @test
     */
    public function spliceNoReplacements()
    {
        $helper = new ArrayHelper();
        $splicedArray = $helper->splice([0, 1, 2, 3, 4, 5], 2, 2);
        $this->assertEquals([0, 1, 4, 5], $splicedArray);
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

        $this->assertEquals($expected, $result);
    }
}
