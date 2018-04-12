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

use Neos\Eel\FlowQuery\Operations\SliceOperation;

/**
 * SliceOperation test
 */
class SliceOperationTest extends \Neos\Flow\Tests\UnitTestCase
{
    public function sliceExamples()
    {
        return [
            'no argument' => [['a', 'b', 'c'], [], ['a', 'b', 'c']],
            'empty array' => [[], [1], []],
            'empty array with end' => [[], [1, 5], []],
            'slice in bounds' => [['a', 'b', 'c', 'd'], [1, 3], ['b', 'c']],
            'positive start' => [['a', 'b', 'c', 'd'], [2], ['c', 'd']],
            'negative start' => [['a', 'b', 'c', 'd'], [-1], ['d']],
            'end out of bounds' => [['a', 'b', 'c', 'd'], [3, 10], ['d']],
            'negative start and end' => [['a', 'b', 'c', 'd'], [-3, -1], ['b', 'c']],
        ];
    }

    /**
     * @test
     * @dataProvider sliceExamples
     */
    public function evaluateSetsTheCorrectPartOfTheContextArray($value, $arguments, $expected)
    {
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery($value);

        $operation = new SliceOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertEquals($expected, $flowQuery->getContext());
    }
}
