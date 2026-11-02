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
use Neos\Eel\FlowQuery\Operations\Object\ChildrenOperation;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * ChildrenOperation test
 */
final class ChildrenOperationTest extends UnitTestCase
{
    public static function childrenExamples(): \Iterator
    {
        $object1 = (object) ['a' => 'b'];
        $object2 = (object) ['c' => 'd'];

        $exampleArray = [
            'keyTowardsObject' => ((object) []),
            'keyTowardsArray' => [$object1, $object2],
            'keyTowardsTraversable' => new \ArrayIterator([$object1, $object2])
        ];
        yield 'traversal of objects' => [[$exampleArray], ['keyTowardsObject'], [$exampleArray['keyTowardsObject']]];
        yield 'traversal of arrays unrolls them' => [[$exampleArray], ['keyTowardsArray'], [$object1, $object2]];
        yield 'traversal of traversables unrolls them' => [[$exampleArray], ['keyTowardsTraversable'], [$object1, $object2]];
    }

    #[DataProvider('childrenExamples')]
    #[Test]
    public function evaluateSetsTheCorrectPartOfTheContextArray($value, $arguments, $expected)
    {
        $flowQuery = new FlowQuery($value);

        $operation = new ChildrenOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertEquals($expected, $flowQuery->getContext());
    }
}
