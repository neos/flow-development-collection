<?php
namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\Helper\MathHelper;

/**
 * Tests for MathHelper
 */
class MathHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * Define a "not a number" constant for comparison (because NAN !== NAN)
     */
    const NAN = 'NAN';

    public function roundExamples()
    {
        return [
            'round with default precision' => [123.4567, null, 123],
            'round with 2 digit precision' => [123.4567, 2, 123.46],
            'round with negative precision' => [123.4567, -1, 120],
            'round with integer' => [1234, null, 1234],
            'round with string' => ['foo', null, static::NAN],
            'round with float precision' => [123.4567, 1.5, static::NAN]
        ];
    }

    /**
     * @test
     * @dataProvider roundExamples
     */
    public function roundWorks($value, $precision, $expected)
    {
        $helper = new MathHelper();
        $result = $helper->round($value, $precision);
        if ($expected === static::NAN) {
            $this->assertTrue(is_nan($result), 'Expected NAN');
        } else {
            $this->assertEquals($expected, $result, 'Rounded value did not match', 0.0001);
        }
    }

    public function constantsExamples()
    {
        return [
            'E' => ['Math.E', 2.718],
            'LN2' => ['Math.LN2', 0.693],
            'LN10' => ['Math.LN10', 2.303],
            'LOG2E' => ['Math.LOG2E', 1.443],
            'LOG10E' => ['Math.LOG10E', 0.434],
            'PI' => ['Math.PI', 3.14159],
            'SQRT1_2' => ['Math.SQRT1_2', 0.707],
            'SQRT2' => ['Math.SQRT2', 1.414],
        ];
    }

    /**
     * @test
     * @dataProvider constantsExamples
     */
    public function constantsWorks($method, $expected)
    {
        $helper = new MathHelper();
        $evaluator = new \Neos\Eel\InterpretedEvaluator();
        $context = new \Neos\Eel\Context([
            'Math' => $helper
        ]);
        $result = $evaluator->evaluate($method, $context);
        $this->assertEquals($expected, $result, 'Rounded value did not match', 0.001);
    }

    public function trigonometricExamples()
    {
        return [
            'acos(x)' => ['Math.acos(-1)', 3.14159],
            'acosh(x)' => ['Math.acosh(2)', 1.3169],
            'asin(x)' => ['Math.asin(0.5)', 0.5235],
            'asinh(x)' => ['Math.asinh(1)', 0.881373587019543],
            'atan(x)' => ['Math.atan(1)', 0.7853],
            'atanh(x)' => ['Math.atanh(0.5)', 0.5493],
            'atan2(y, x)' => ['Math.atan2(90, 15)', 1.4056],
            'cos(x)' => ['Math.cos(Math.PI)', -1],
            'cosh(x)' => ['Math.cosh(1)', 1.54308],
            'sin(x)' => ['Math.sin(1)', 0.8414],
            'sinh(x)' => ['Math.sinh(1)', 1.1752],
            'tan(x)' => ['Math.tan(1)', 1.5574],
            'tanh(x)' => ['Math.tanh(1)', 0.7615],
        ];
    }

    /**
     * @test
     * @dataProvider trigonometricExamples
     */
    public function trigonometricFunctionsWork($method, $expected)
    {
        $helper = new MathHelper();
        $evaluator = new \Neos\Eel\InterpretedEvaluator();
        $context = new \Neos\Eel\Context([
            'Math' => $helper
        ]);
        $result = $evaluator->evaluate($method, $context);
        $this->assertEquals($expected, $result, 'Rounded value did not match', 0.001);
    }

    public function variousExamples()
    {
        return [
            'abs("-1")' => ['Math.abs("-1")', 1],
            'abs(-2)' => ['Math.abs(-2)', 2],
            'abs(null)' => ['Math.abs(null)', 0],
            'abs("string")' => ['Math.abs("string")', static::NAN],
            'abs()' => ['Math.abs()', static::NAN],

            'cbrt(-1)' => ['Math.cbrt(-1)', -1],
            'cbrt(2)' => ['Math.cbrt(2)', 1.2599],

            'ceil(0.95)' => ['Math.ceil(0.95)', 1],
            'ceil(4)' => ['Math.ceil(4)', 4],
            'ceil(7.004)' => ['Math.ceil(7.004)', 8],
            'ceil(-1.004)' => ['Math.ceil(-1.004)', -1],

            'exp(-1)' => ['Math.exp(-1)', 0.3678],
            'exp(0)' => ['Math.exp(0)', 1],
            'exp(1)' => ['Math.exp(1)', 2.7182],

            'expm1(-1)' => ['Math.expm1(-1)', -0.6321],
            'expm1(0)' => ['Math.expm1(0)', 0],
            'expm1(1)' => ['Math.expm1(1)', 1.7182],

            'floor(0.95)' => ['Math.floor(0.95)', 0],
            'floor(4)' => ['Math.floor(4)', 4],
            'floor(-1.004)' => ['Math.floor(-1.004)', -2],

            'hypot(3, 4)' => ['Math.hypot(3, 4)', 5],
            'hypot(3, 4, 5)' => ['Math.hypot(3, 4, 5)', 7.0710],

            'log(-1)' => ['Math.log(-1)', static::NAN],
            'log(0)' => ['Math.log(0)', -INF],
            'log(1)' => ['Math.log(1)', 0],
            'log(10)' => ['Math.log(10)', 2.3025],

            'log1p(1)' => ['Math.log1p(1)', 0.6931],
            'log1p(0)' => ['Math.log1p(0)', 0],
            'log1p(-1)' => ['Math.log1p(-1)', -INF],
            'log1p(-2)' => ['Math.log1p(-2)', static::NAN],

            'log10(2)' => ['Math.log10(2)', 0.3010],
            'log10(1)' => ['Math.log10(1)', 0],
            'log10(0)' => ['Math.log10(0)', -INF],
            'log10(-2)' => ['Math.log10(-2)', static::NAN],

            'log2(3)' => ['Math.log2(3)', 1.5849],
            'log2(2)' => ['Math.log2(2)', 1],
            'log2(1)' => ['Math.log2(1)', 0],
            'log2(0)' => ['Math.log2(0)', -INF],
            'log2(-2)' => ['Math.log2(-2)', static::NAN],

            'max()' => ['Math.max()', -INF],
            'max(10, 20)' => ['Math.max(10, 20)', 20],
            'max(-10, -20)' => ['Math.max(-10, -20)', -10],

            'min()' => ['Math.min()', INF],
            'min(10, 20)' => ['Math.min(10, 20)', 10],
            'min(-10, -20)' => ['Math.min(-10, -20)', -20],

            'pow(2, 3)' => ['Math.pow(2, 3)', 8],
            'pow(2, 0.5)' => ['Math.pow(2, 0.5)', 1.41421],

            'sign(3)' => ['Math.sign(3)', 1],
            'sign(-3.5)' => ['Math.sign(-3.5)', -1],
            'sign("-3")' => ['Math.sign("-3")', -1],
            'sign(0)' => ['Math.sign(0)', 0],
            'sign(0.0)' => ['Math.sign(0.0)', 0],
            'sign("foo")' => ['Math.sign("foo")', static::NAN],

            'sqrt(9)' => ['Math.sqrt(9)', 3],
            'sqrt(2)' => ['Math.sqrt(2)', 1.41421],
            'sqrt(0)' => ['Math.sqrt(0)', 0],
            'sqrt(-1)' => ['Math.sqrt(-1)', static::NAN],

            'trunc(13.37)' => ['Math.trunc(13.37)', 13],
            'trunc(-0.123)' => ['Math.trunc(-0.123)', 0],
            'trunc("-1.123")' => ['Math.trunc("-1.123")', -1],
            'trunc(0)' => ['Math.trunc(0)', 0],
            'trunc(0.0)' => ['Math.trunc(0.0)', 0],
            'trunc("foo")' => ['Math.trunc("foo")', static::NAN],
        ];
    }

    /**
     * @test
     * @dataProvider variousExamples
     */
    public function variousFunctionsWork($method, $expected)
    {
        $helper = new MathHelper();
        $evaluator = new \Neos\Eel\InterpretedEvaluator();
        $context = new \Neos\Eel\Context([
            'Math' => $helper
        ]);
        $result = $evaluator->evaluate($method, $context);
        if ($expected === static::NAN) {
            $this->assertTrue(is_nan($result), 'Expected NAN, got value "' . $result . '"');
        } else {
            $this->assertEquals($expected, $result, 'Rounded value did not match', 0.001);
        }
    }

    public function finiteAndNanExamples()
    {
        return [
            'isFinite(42)' => ['isFinite', 42, true],
            'isFinite(NAN)' => ['isFinite', NAN, false],
            'isFinite(INF)' => ['isFinite', INF, false],
            'isFinite("42")' => ['isFinite', '42', true],
            'isFinite("foo")' => ['isFinite', 'foo', false],

            'isInfinite(42)' => ['isInfinite', 42, false],
            'isInfinite(NAN)' => ['isInfinite', NAN, false],
            'isInfinite(INF)' => ['isInfinite', INF, true],
            'isInfinite(-INF)' => ['isInfinite', -INF, true],
            'isInfinite("42")' => ['isInfinite', '42', false],
            'isInfinite("foo")' => ['isInfinite', 'foo', false],

            'isNaN(42)' => ['isNaN', 42, false],
            'isNaN(NAN)' => ['isNaN', NAN, true],
            'isNaN("42")' => ['isNaN', '42', false],
            'isNaN("foo")' => ['isNaN', 'foo', true],
            'isNaN(INF)' => ['isNaN', INF, false],
        ];
    }

    /**
     * @test
     * @dataProvider finiteAndNanExamples
     */
    public function finiteAndNanFunctionsWork($method, $value, $expected)
    {
        $helper = new MathHelper();
        $result = $helper->$method($value);

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function randomReturnsARandomResultFromZeroToOneExclusive()
    {
        $helper = new MathHelper();
        $r1 = $helper->random();
        $atLeastOneRandomResult = false;
        for ($i = 0; $i < 100; $i++) {
            $ri = $helper->random();
            if ($ri !== $r1) {
                $atLeastOneRandomResult = true;
            }
            $this->assertLessThan(1.0, $ri, 'Result should be less than 1');
            $this->assertGreaterThanOrEqual(0.0, $ri, 'Result should be greater than 0');
        }
        $this->assertTrue($atLeastOneRandomResult, 'random() should return a random result');
    }

    /**
     * @test
     */
    public function randomIntReturnsARandomResultFromMinToMaxExclusive()
    {
        $helper = new MathHelper();
        $min = 10;
        $max = 42;
        $r1 = $helper->randomInt($min, $max);
        $atLeastOneRandomResult = false;
        for ($i = 0; $i < 100; $i++) {
            $ri = $helper->randomInt($min, $max);
            if ($ri !== $r1) {
                $atLeastOneRandomResult = true;
            }
            $this->assertLessThanOrEqual($max, $ri);
            $this->assertGreaterThanOrEqual($min, $ri);
        }
        $this->assertTrue($atLeastOneRandomResult, 'random() should return a random result');
    }
}
