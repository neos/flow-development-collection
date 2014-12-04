<?php
namespace TYPO3\Eel\Tests\Unit\FlowQuery\Operations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\Operations\AddOperation;

/**
 * AddOperation test
 */
class AddOperationTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * This corresponds to ${q(node).add(q(someOtherNode))}
	 *
	 * @test
	 */
	public function addWithFlowQueryArgumentAppendsToCurrentContext() {
		$mockNode1 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode2 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

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
	public function addWithNodeArgumentAppendsToCurrentContext() {
		$mockNode1 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode2 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

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
	public function addWithArrayArgumentAppendsToCurrentContext() {
		$mockNode1 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode2 = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery(array($mockNode1));

		$arrayArgument = array($mockNode2);
		$arguments = array($arrayArgument);

		$operation = new AddOperation();
		$operation->evaluate($flowQuery, $arguments);

		$this->assertSame(array($mockNode1, $mockNode2), $flowQuery->getContext());
	}
}
