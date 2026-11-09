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
use Neos\Eel\FlowQuery\Operations\AddOperation;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * AddOperation test
 */
final class AddOperationTest extends UnitTestCase
{
    /**
     * This corresponds to ${q(node).add(q(someOtherNode))}
     */
    #[Test]
    public function addWithFlowQueryArgumentAppendsToCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery([$object1]);

        $flowQueryArgument = new FlowQuery([$object2]);
        $arguments = [$flowQueryArgument];

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertSame([$object1, $object2], $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).add(someOtherNode)}
     */
    #[Test]
    public function addWithNodeArgumentAppendsToCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery([$object1]);

        $nodeArgument = $object2;
        $arguments = [$nodeArgument];

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertSame([$object1, $object2], $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).add([someOtherNode, ...]))}
     */
    #[Test]
    public function addWithArrayArgumentAppendsToCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery([$object1]);

        $arrayArgument = [$object2];
        $arguments = [$arrayArgument];

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertSame([$object1, $object2], $flowQuery->getContext());
    }
}
