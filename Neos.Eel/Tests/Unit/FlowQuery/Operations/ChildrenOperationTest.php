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

use Neos\Eel\FlowQuery\Operations\Object\ChildrenOperation;

/**
 * ChildrenOperation test
 */
class ChildrenOperationTest extends \Neos\Flow\Tests\UnitTestCase
{
    public function childrenExamples()
    {
        $object1 = (object) ['a' => 'b'];
        $object2 = (object) ['c' => 'd'];

        $exampleArray = [
            'keyTowardsObject' => ((object) []),
            'keyTowardsArray' => [$object1, $object2],
            'keyTowardsTraversable' => new \ArrayIterator([$object1, $object2])
        ];

        return [
            'traversal of objects' => [[$exampleArray], ['keyTowardsObject'], [$exampleArray['keyTowardsObject']]],
            'traversal of arrays unrolls them' => [[$exampleArray], ['keyTowardsArray'], [$object1, $object2]],
            'traversal of traversables unrolls them' => [[$exampleArray], ['keyTowardsTraversable'], [$object1, $object2]],
        ];
    }

    /**
     * @test
     * @dataProvider childrenExamples
     */
    public function evaluateSetsTheCorrectPartOfTheContextArray($value, $arguments, $expected)
    {
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery($value);

        $operation = new ChildrenOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertEquals($expected, $flowQuery->getContext());
    }
}
