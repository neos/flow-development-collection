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
        $mockNode1 = $this->getMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);
        $mockNode2 = $this->getMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);

        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery(array($mockNode1));

        $flowQueryArgument = new \TYPO3\Eel\FlowQuery\FlowQuery(array($mockNode2));
        $arguments = array($flowQueryArgument);

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertSame(array($mockNode1, $mockNode2), $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).add(someOtherNode)}
     *
     * @test
     */
    public function addWithNodeArgumentAppendsToCurrentContext()
    {
        $mockNode1 = $this->getMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);
        $mockNode2 = $this->getMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);

        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery(array($mockNode1));

        $nodeArgument = $mockNode2;
        $arguments = array($nodeArgument);

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertSame(array($mockNode1, $mockNode2), $flowQuery->getContext());
    }

    /**
     * This corresponds to ${q(node).add([someOtherNode, ...]))}
     *
     * @test
     */
    public function addWithArrayArgumentAppendsToCurrentContext()
    {
        $mockNode1 = $this->getMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);
        $mockNode2 = $this->getMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);

        $flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery(array($mockNode1));

        $arrayArgument = array($mockNode2);
        $arguments = array($arrayArgument);

        $operation = new AddOperation();
        $operation->evaluate($flowQuery, $arguments);

        $this->assertSame(array($mockNode1, $mockNode2), $flowQuery->getContext());
    }
}
