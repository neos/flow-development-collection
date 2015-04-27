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

	/**
	 * Assert that the expression is evaluated to the expected result
	 * under the given context. It also ensures that the Eel expression is
	 * recognized using the predefined regular expression.
	 *
	 * @param mixed $expected
	 * @param string $expression
	 * @param \TYPO3\Eel\Context $context
	 */
	protected function assertEvaluated($expected, $expression, $context) {
		$evaluator = $this->getAccessibleMock('TYPO3\Eel\CompilingEvaluator', array('dummy'));
		// note, this is not a public method. We should expect expressions coming in here to be trimmed already.
		$code = $evaluator->_call('generateEvaluatorCode', trim($expression));
		$this->assertSame($expected, $evaluator->evaluate($expression, $context), 'Code ' . $code . ' should evaluate to expected result');

		$wrappedExpression = '${' . $expression . '}';
		$this->assertSame(1, preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $wrappedExpression), 'The wrapped expression ' . $wrappedExpression . ' was not detected as Eel expression');
	}

}
