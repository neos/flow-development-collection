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
use Neos\Eel\FlowQuery\Operations\RemoveOperation;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * RemoveOperation test
 */
final class RemoveOperationTest extends UnitTestCase
{
    /**
     * This corresponds to ${q(node).remove(q(someOtherNode))}
     */
    #[Test]
    public function removeWithFlowQueryArgumentRemovesFromCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery([$object1, $object2]);

        $flowQueryArgument = new FlowQuery([$object2]);
        $arguments = [$flowQueryArgument];

        $operation = new RemoveOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertSame([$object1], $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).remove(someOtherNode)}
     */
    #[Test]
    public function removeWithNodeArgumentRemovesFromCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery([$object1, $object2]);

        $nodeArgument = $object2;
        $arguments = [$nodeArgument];

        $operation = new RemoveOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertSame([$object1], $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).remove([someOtherNode, ...]))}
     */
    #[Test]
    public function removeWithArrayArgumentRemovesFromCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery([$object1, $object2]);

        $arrayArgument = [$object2];
        $arguments = [$arrayArgument];

        $operation = new RemoveOperation();
        $operation->evaluate($flowQuery, $arguments);

        self::assertSame([$object1], $flowQuery->getContext());
    }
}
