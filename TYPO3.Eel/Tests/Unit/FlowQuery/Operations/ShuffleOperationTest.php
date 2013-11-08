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

use TYPO3\Eel\FlowQuery\Operations\ShuffleOperation;

/**
 * ShuffleOperation test
 */
class ShuffleOperationTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function evaluateRandomizesTheContextArray() {
		$value = range(1, 1000);
		$flowQuery = new \TYPO3\Eel\FlowQuery\FlowQuery($value);

		$operation = new ShuffleOperation();
		$operation->evaluate($flowQuery, array());

		$this->assertSame(count($value), count($flowQuery->getContext()));
		$this->assertNotEquals($value, $flowQuery->getContext());
	}

}
