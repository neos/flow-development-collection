<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Context;
use TYPO3\Eel\CompilingEvaluator;

/**
 * Compiling evaluator test
 */
class CompilingEvaluatorTest extends AbstractEvaluatorTest {

	/**
	 * @return \TYPO3\Eel\Context
	 */
	protected function createEvaluator() {
		return new CompilingEvaluator();
	}

	/**
	 * @test
	 */
	public function doubleQuotedStringLiteralVariablesAreEscaped() {
		$context = new Context('hidden');
		$this->assertEvaluated('some {$context->unwrap()} string with \'quoted stuff\'', '"some {$context->unwrap()} string with \'quoted stuff\'"', $context);
	}

}
?>