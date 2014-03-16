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

use TYPO3\Eel\Helper\MathHelper;

/**
 * Tests for MathHelper
 */
class MathHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Define a "not a number" constant for comparison (because NAN !== NAN)
	 */
	const NAN = 'NAN';

	public function roundExamples() {
		return array(
			'round with default precision' => array(123.4567, NULL, 123),
			'round with 2 digit precision' => array(123.4567, 2, 123.46),
			'round with negative precision' => array(123.4567, -1, 120),
			'round with integer' => array(1234, NULL, 1234),
			'round with string' => array('foo', NULL, static::NAN),
			'round with float precision' => array(123.4567, 1.5, static::NAN)
		);
	}

	/**
	 * @test
	 * @dataProvider roundExamples
	 */
	public function roundWorks($value, $precision, $expected) {
		$helper = new MathHelper();
		$result = $helper->round($value, $precision);
		if ($expected === static::NAN) {
			$this->assertTrue(is_nan($result), 'Expected NAN');
		} else {
			$this->assertEquals($expected, $result, 'Rounded value did not match', 0.0001);
		}
	}

	public function constantsExamples() {
		return array(
			'E' => array('Math.E', 2.718),
			'LN2' => array('Math.LN2', 0.693),
			'LN10' => array('Math.LN10', 2.303),
			'LOG2E' => array('Math.LOG2E', 1.443),
			'LOG10E' => array('Math.LOG10E', 0.434),
			'PI' => array('Math.PI', 3.14159),
			'SQRT1_2' => array('Math.SQRT1_2', 0.707),
			'SQRT2' => array('Math.SQRT2', 1.414),
		);
	}

	/**
	 * @test
	 * @dataProvider constantsExamples
	 */
	public function constantsWorks($method, $expected) {
		$helper = new MathHelper();
		$evaluator = new \TYPO3\Eel\InterpretedEvaluator();
		$context = new \TYPO3\Eel\Context(array(
			'Math' => $helper
		));
		$result = $evaluator->evaluate($method, $context);
		$this->assertEquals($expected, $result, 'Rounded value did not match', 0.001);
	}

	public function trigonometricExamples() {
		return array(
			'acos(x)' => array('Math.acos(-1)', 3.14159),
			'acosh(x)' => array('Math.acosh(2)', 1.3169),
			'asin(x)' => array('Math.asin(0.5)', 0.5235),
			'asinh(x)' => array('Math.asinh(1)', 0.881373587019543),
			'atan(x)' => array('Math.atan(1)', 0.7853),
			'atanh(x)' => array('Math.atanh(0.5)', 0.5493),
			'atan2(y, x)' => array('Math.atan2(90, 15)', 1.4056),
			'cos(x)' => array('Math.cos(Math.PI)', -1),
			'cosh(x)' => array('Math.cosh(1)', 1.54308),
			'sin(x)' => array('Math.sin(1)', 0.8414),
			'sinh(x)' => array('Math.sinh(1)', 1.1752),
			'tan(x)' => array('Math.tan(1)', 1.5574),
			'tanh(x)' => array('Math.tanh(1)', 0.7615),
		);
	}

	/**
	 * @test
	 * @dataProvider trigonometricExamples
	 */
	public function trigonometricFunctionsWork($method, $expected) {
		$helper = new MathHelper();
		$evaluator = new \TYPO3\Eel\InterpretedEvaluator();
		$context = new \TYPO3\Eel\Context(array(
			'Math' => $helper
		));
		$result = $evaluator->evaluate($method, $context);
		$this->assertEquals($expected, $result, 'Rounded value did not match', 0.001);
	}

	public function variousExamples() {
		return array(
			'abs("-1")' => array('Math.abs("-1")', 1),
			'abs(-2)' => array('Math.abs(-2)', 2),
			'abs(null)' => array('Math.abs(null)', 0),
			'abs("string")' => array('Math.abs("string")', static::NAN),
			'abs()' => array('Math.abs()', static::NAN),

			'cbrt(-1)' => array('Math.cbrt(-1)', -1),
			'cbrt(2)' => array('Math.cbrt(2)', 1.2599),

			'ceil(0.95)' => array('Math.ceil(0.95)', 1),
			'ceil(4)' => array('Math.ceil(4)', 4),
			'ceil(7.004)' => array('Math.ceil(7.004)', 8),
			'ceil(-1.004)' => array('Math.ceil(-1.004)', -1),

			'exp(-1)' => array('Math.exp(-1)', 0.3678),
			'exp(0)' => array('Math.exp(0)', 1),
			'exp(1)' => array('Math.exp(1)', 2.7182),

			'expm1(-1)' => array('Math.expm1(-1)', -0.6321),
			'expm1(0)' => array('Math.expm1(0)', 0),
			'expm1(1)' => array('Math.expm1(1)', 1.7182),

			'floor(0.95)' => array('Math.floor(0.95)', 0),
			'floor(4)' => array('Math.floor(4)', 4),
			'floor(-1.004)' => array('Math.floor(-1.004)', -2),

			'hypot(3, 4)' => array('Math.hypot(3, 4)', 5),
			'hypot(3, 4, 5)' => array('Math.hypot(3, 4, 5)', 7.0710),

			'log(-1)' => array('Math.log(-1)', static::NAN),
			'log(0)' => array('Math.log(0)', -INF),
			'log(1)' => array('Math.log(1)', 0),
			'log(10)' => array('Math.log(10)', 2.3025),

			'log1p(1)' => array('Math.log1p(1)', 0.6931),
			'log1p(0)' => array('Math.log1p(0)', 0),
			'log1p(-1)' => array('Math.log1p(-1)', -INF),
			'log1p(-2)' => array('Math.log1p(-2)', static::NAN),

			'log10(2)' => array('Math.log10(2)', 0.3010),
			'log10(1)' => array('Math.log10(1)', 0),
			'log10(0)' => array('Math.log10(0)', -INF),
			'log10(-2)' => array('Math.log10(-2)', static::NAN),

			'log2(3)' => array('Math.log2(3)', 1.5849),
			'log2(2)' => array('Math.log2(2)', 1),
			'log2(1)' => array('Math.log2(1)', 0),
			'log2(0)' => array('Math.log2(0)', -INF),
			'log2(-2)' => array('Math.log2(-2)', static::NAN),

			'max()' => array('Math.max()', -INF),
			'max(10, 20)' => array('Math.max(10, 20)', 20),
			'max(-10, -20)' => array('Math.max(-10, -20)', -10),

			'min()' => array('Math.min()', INF),
			'min(10, 20)' => array('Math.min(10, 20)', 10),
			'min(-10, -20)' => array('Math.min(-10, -20)', -20),

			'pow(2, 3)' => array('Math.pow(2, 3)', 8),
			'pow(2, 0.5)' => array('Math.pow(2, 0.5)', 1.41421),

			'sign(3)' => array('Math.sign(3)', 1),
			'sign(-3.5)' => array('Math.sign(-3.5)', -1),
			'sign("-3")' => array('Math.sign("-3")', -1),
			'sign(0)' => array('Math.sign(0)', 0),
			'sign(0.0)' => array('Math.sign(0.0)', 0),
			'sign("foo")' => array('Math.sign("foo")', static::NAN),

			'sqrt(9)' => array('Math.sqrt(9)', 3),
			'sqrt(2)' => array('Math.sqrt(2)', 1.41421),
			'sqrt(0)' => array('Math.sqrt(0)', 0),
			'sqrt(-1)' => array('Math.sqrt(-1)', static::NAN),

			'trunc(13.37)' => array('Math.trunc(13.37)', 13),
			'trunc(-0.123)' => array('Math.trunc(-0.123)', 0),
			'trunc("-1.123")' => array('Math.trunc("-1.123")', -1),
			'trunc(0)' => array('Math.trunc(0)', 0),
			'trunc(0.0)' => array('Math.trunc(0.0)', 0),
			'trunc("foo")' => array('Math.trunc("foo")', static::NAN),
		);
	}

	/**
	 * @test
	 * @dataProvider variousExamples
	 */
	public function variousFunctionsWork($method, $expected) {
		$helper = new MathHelper();
		$evaluator = new \TYPO3\Eel\InterpretedEvaluator();
		$context = new \TYPO3\Eel\Context(array(
			'Math' => $helper
		));
		$result = $evaluator->evaluate($method, $context);
		if ($expected === static::NAN) {
			$this->assertTrue(is_nan($result), 'Expected NAN, got value "' . $result . '"');
		} else {
			$this->assertEquals($expected, $result, 'Rounded value did not match', 0.001);
		}
	}

	public function finiteAndNanExamples() {
		return array(
			'isFinite(42)' => array('isFinite', 42, TRUE),
			'isFinite(NAN)' => array('isFinite', NAN, FALSE),
			'isFinite(INF)' => array('isFinite', INF, FALSE),
			'isFinite("42")' => array('isFinite', '42', TRUE),
			'isFinite("foo")' => array('isFinite', 'foo', FALSE),

			'isInfinite(42)' => array('isInfinite', 42, FALSE),
			'isInfinite(NAN)' => array('isInfinite', NAN, FALSE),
			'isInfinite(INF)' => array('isInfinite', INF, TRUE),
			'isInfinite(-INF)' => array('isInfinite', -INF, TRUE),
			'isInfinite("42")' => array('isInfinite', '42', FALSE),
			'isInfinite("foo")' => array('isInfinite', 'foo', FALSE),

			'isNaN(42)' => array('isNaN', 42, FALSE),
			'isNaN(NAN)' => array('isNaN', NAN, TRUE),
			'isNaN("42")' => array('isNaN', '42', FALSE),
			'isNaN("foo")' => array('isNaN', 'foo', TRUE),
			'isNaN(INF)' => array('isNaN', INF, FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider finiteAndNanExamples
	 */
	public function finiteAndNanFunctionsWork($method, $value, $expected) {
		$helper = new MathHelper();
		$result = $helper->$method($value);

		$this->assertSame($expected, $result);
	}

	/**
	 * @test
	 */
	public function randomReturnsARandomResultFromZeroToOneExclusive() {
		$helper = new MathHelper();
		$r1 = $helper->random();
		$atLeastOneRandomResult = FALSE;
		for ($i = 0; $i < 100; $i++) {
			$ri = $helper->random();
			if ($ri !== $r1) {
				$atLeastOneRandomResult = TRUE;
			}
			$this->assertLessThan(1.0, $ri, 'Result should be less than 1');
			$this->assertGreaterThanOrEqual(0.0, $ri, 'Result should be greater than 0');
		}
		$this->assertTrue($atLeastOneRandomResult, 'random() should return a random result');
	}

	/**
	 * @test
	 */
	public function randomIntReturnsARandomResultFromMinToMaxExclusive() {
		$helper = new MathHelper();
		$min = 10;
		$max = 42;
		$r1 = $helper->randomInt($min, $max);
		$atLeastOneRandomResult = FALSE;
		for ($i = 0; $i < 100; $i++) {
			$ri = $helper->randomInt($min, $max);
			if ($ri !== $r1) {
				$atLeastOneRandomResult = TRUE;
			}
			$this->assertLessThanOrEqual($max, $ri);
			$this->assertGreaterThanOrEqual($min, $ri);
		}
		$this->assertTrue($atLeastOneRandomResult, 'random() should return a random result');
	}

}
