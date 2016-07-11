<?php
namespace TYPO3\Eel\Tests\Unit\FlowQuery\Operations;

/*
 * This file is part of the TYPO3.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AddOperation;

/**
 * AddOperation test
 */
class AddOperationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * This corresponds to ${q(node).add(q(someOtherNode))}
     *
     * @test
     */
    public function addWithFlowQueryArgumentAppendsToCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery(array($object1));

        $flowQueryArgument = new FlowQuery(array($object2));
        $arguments = array($flowQueryArgument);

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertSame(array($object1, $object2), $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).add(someOtherNode)}
     *
     * @test
     */
    public function addWithNodeArgumentAppendsToCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery(array($object1));

        $nodeArgument = $object2;
        $arguments = array($nodeArgument);

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertSame(array($object1, $object2), $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).add([someOtherNode, ...]))}
     *
     * @test
     */
    public function addWithArrayArgumentAppendsToCurrentContext()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $flowQuery = new FlowQuery(array($object1));

        $arrayArgument = array($object2);
        $arguments = array($arrayArgument);

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertSame(array($object1, $object2), $flowQuery->getContext());
    }
}
