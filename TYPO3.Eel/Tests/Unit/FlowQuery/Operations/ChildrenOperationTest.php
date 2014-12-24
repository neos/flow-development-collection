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

use TYPO3\Eel\FlowQuery\Operations\Object\ChildrenOperation;

/**
 * ChildrenOperation test
 */
class ChildrenOperationTest extends \TYPO3\Flow\Tests\UnitTestCase {

	public function childrenExamples() {
		$object1 = (object) array('a' => 'b');
		$object2 = (object) array('c' => 'd');

		$exampleArray = array(
			'keyTowardsObject' => ((object) array()),
			'keyTowardsArray' => array($object1, $object2),
			'keyTowardsTraversable' => new \ArrayIterator(array($object1, $object2))
		);

		return array(
			'traversal of objects' => array(array($exampleArray), array('keyTowardsObject'), array($exampleArray['keyTowardsObject'])),
			'traversal of arrays unrolls them' => array(array($exampleArray), array('keyTowardsArray'), array($object1, $object2)),
			'traversal of traversables unrolls them' => array(array($exampleArray), array('keyTowardsTraversable'), array($object1, $object2)),
		);
	}

	/**
	 * @test
	 * @dataProvider childrenExamples
	 */
	public function evaluateSetsTheCorrectPartOfTheContextArray($value, $arguments, $expected) {
		$flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($value);

		$operation = new ChildrenOperation();
		$operation->evaluate($flowQuery, $arguments);

		$this->assertEquals($expected, $flowQuery->getContext());
	}

}
