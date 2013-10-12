<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Eel\Context;
use Eel\InterpretedEvaluator;

/**
 * A benchmark to test the interpreting evaluator
 *
 * @group benchmark
 */
class InterpretedEvaluatorBenchmarkTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function loopedExpressions() {
		$this->markTestSkipped('Enable for benchmark');

		$evaluator = new InterpretedEvaluator();
		$expression = 'foo.bar=="Test"||foo.baz=="Test"||reverse(foo).bar=="Test"';
		$context = new Context(array(
			'foo' => array(
				'bar' => 'Test1',
				'baz' => 'Test2'
			),
			'reverse' => function($array) {
				return array_reverse($array, TRUE);
			}
		));
		for ($i = 0; $i < 10000; $i++) {
			$evaluator->evaluate($expression, $context);
		}
	}

}
