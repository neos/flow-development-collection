<?php

declare(strict_types=1);

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
use Neos\Eel\Context;
use Neos\Eel\Helper\MathHelper;
use Neos\Eel\InterpretedEvaluator;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for MathHelper
 */
final class MathHelperTest extends UnitTestCase
{
    /**
     * Define a "not a number" constant for comparison (because NAN !== NAN)
     */
    public const NAN = 'NAN';

    public static function roundExamples(): \Iterator
    {
        yield 'round with default precision' => [123.4567, null, 123];
        yield 'round with 2 digit precision' => [123.4567, 2, 123.46];
        yield 'round with negative precision' => [123.4567, -1, 120];
        yield 'round with integer' => [1234, null, 1234];
        yield 'round with string' => ['foo', null, static::NAN];
        yield 'round with float precision' => [123.4567, 1.5, static::NAN];
    }

    #[DataProvider('roundExamples')]
    #[Test]
    public function roundWorks($value, $precision, $expected): void
    {
        $helper = new MathHelper();
        $result = $helper->round($value, $precision);
        if ($expected === static::NAN) {
            self::assertNan($result, 'Expected NAN');
        } else {
            self::assertEqualsWithDelta($expected, $result, 0.0001, 'Rounded value did not match');
        }
    }

    public static function constantsExamples(): \Iterator
    {
        yield 'E' => ['Math.E', 2.718];
        yield 'LN2' => ['Math.LN2', 0.693];
        yield 'LN10' => ['Math.LN10', 2.303];
        yield 'LOG2E' => ['Math.LOG2E', 1.443];
        yield 'LOG10E' => ['Math.LOG10E', 0.434];
        yield 'PI' => ['Math.PI', 3.14159];
        yield 'SQRT1_2' => ['Math.SQRT1_2', 0.707];
        yield 'SQRT2' => ['Math.SQRT2', 1.414];
    }

    #[DataProvider('constantsExamples')]
    #[Test]
    public function constantsWorks($method, $expected): void
    {
        $helper = new MathHelper();
        $evaluator = new InterpretedEvaluator();
        $context = new Context([
            'Math' => $helper
        ]);
        $result = $evaluator->evaluate($method, $context);
        self::assertEqualsWithDelta($expected, $result, 0.001, 'Rounded value did not match');
    }

    public static function trigonometricExamples(): \Iterator
    {
        yield 'acos(x)' => ['Math.acos(-1)', 3.14159];
        yield 'acosh(x)' => ['Math.acosh(2)', 1.3169];
        yield 'asin(x)' => ['Math.asin(0.5)', 0.5235];
        yield 'asinh(x)' => ['Math.asinh(1)', 0.881373587019543];
        yield 'atan(x)' => ['Math.atan(1)', 0.7853];
        yield 'atanh(x)' => ['Math.atanh(0.5)', 0.5493];
        yield 'atan2(y, x)' => ['Math.atan2(90, 15)', 1.4056];
        yield 'cos(x)' => ['Math.cos(Math.PI)', -1];
        yield 'cosh(x)' => ['Math.cosh(1)', 1.54308];
        yield 'sin(x)' => ['Math.sin(1)', 0.8414];
        yield 'sinh(x)' => ['Math.sinh(1)', 1.1752];
        yield 'tan(x)' => ['Math.tan(1)', 1.5574];
        yield 'tanh(x)' => ['Math.tanh(1)', 0.7615];
    }

    #[DataProvider('trigonometricExamples')]
    #[Test]
    public function trigonometricFunctionsWork($method, $expected): void
    {
        $helper = new MathHelper();
        $evaluator = new InterpretedEvaluator();
        $context = new Context([
            'Math' => $helper
        ]);
        $result = $evaluator->evaluate($method, $context);
        self::assertEqualsWithDelta($expected, $result, 0.001, 'Rounded value did not match');
    }

    public static function variousExamples(): \Iterator
    {
        yield 'abs("-1")' => ['Math.abs("-1")', 1];
        yield 'abs(-2)' => ['Math.abs(-2)', 2];
        yield 'abs(null)' => ['Math.abs(null)', 0];
        yield 'abs("string")' => ['Math.abs("string")', static::NAN];
        yield 'abs()' => ['Math.abs()', static::NAN];
        yield 'cbrt(-1)' => ['Math.cbrt(-1)', -1];
        yield 'cbrt(2)' => ['Math.cbrt(2)', 1.2599];
        yield 'ceil(0.95)' => ['Math.ceil(0.95)', 1];
        yield 'ceil(4)' => ['Math.ceil(4)', 4];
        yield 'ceil(7.004)' => ['Math.ceil(7.004)', 8];
        yield 'ceil(-1.004)' => ['Math.ceil(-1.004)', -1];
        yield 'exp(-1)' => ['Math.exp(-1)', 0.3678];
        yield 'exp(0)' => ['Math.exp(0)', 1];
        yield 'exp(1)' => ['Math.exp(1)', 2.7182];
        yield 'expm1(-1)' => ['Math.expm1(-1)', -0.6321];
        yield 'expm1(0)' => ['Math.expm1(0)', 0];
        yield 'expm1(1)' => ['Math.expm1(1)', 1.7182];
        yield 'floor(0.95)' => ['Math.floor(0.95)', 0];
        yield 'floor(4)' => ['Math.floor(4)', 4];
        yield 'floor(-1.004)' => ['Math.floor(-1.004)', -2];
        yield 'hypot(3, 4)' => ['Math.hypot(3, 4)', 5];
        yield 'hypot(3, 4, 5)' => ['Math.hypot(3, 4, 5)', 7.0710];
        yield 'log(-1)' => ['Math.log(-1)', static::NAN];
        yield 'log(0)' => ['Math.log(0)', -INF];
        yield 'log(1)' => ['Math.log(1)', 0];
        yield 'log(10)' => ['Math.log(10)', 2.3025];
        yield 'log1p(1)' => ['Math.log1p(1)', 0.6931];
        yield 'log1p(0)' => ['Math.log1p(0)', 0];
        yield 'log1p(-1)' => ['Math.log1p(-1)', -INF];
        yield 'log1p(-2)' => ['Math.log1p(-2)', static::NAN];
        yield 'log10(2)' => ['Math.log10(2)', 0.3010];
        yield 'log10(1)' => ['Math.log10(1)', 0];
        yield 'log10(0)' => ['Math.log10(0)', -INF];
        yield 'log10(-2)' => ['Math.log10(-2)', static::NAN];
        yield 'log2(3)' => ['Math.log2(3)', 1.5849];
        yield 'log2(2)' => ['Math.log2(2)', 1];
        yield 'log2(1)' => ['Math.log2(1)', 0];
        yield 'log2(0)' => ['Math.log2(0)', -INF];
        yield 'log2(-2)' => ['Math.log2(-2)', static::NAN];
        yield 'max()' => ['Math.max()', -INF];
        yield 'max(10, 20)' => ['Math.max(10, 20)', 20];
        yield 'max(-10, -20)' => ['Math.max(-10, -20)', -10];
        yield 'min()' => ['Math.min()', INF];
        yield 'min(10, 20)' => ['Math.min(10, 20)', 10];
        yield 'min(-10, -20)' => ['Math.min(-10, -20)', -20];
        yield 'pow(2, 3)' => ['Math.pow(2, 3)', 8];
        yield 'pow(2, 0.5)' => ['Math.pow(2, 0.5)', 1.41421];
        yield 'sign(3)' => ['Math.sign(3)', 1];
        yield 'sign(-3.5)' => ['Math.sign(-3.5)', -1];
        yield 'sign("-3")' => ['Math.sign("-3")', -1];
        yield 'sign(0)' => ['Math.sign(0)', 0];
        yield 'sign(0.0)' => ['Math.sign(0.0)', 0];
        yield 'sign("foo")' => ['Math.sign("foo")', static::NAN];
        yield 'sqrt(9)' => ['Math.sqrt(9)', 3];
        yield 'sqrt(2)' => ['Math.sqrt(2)', 1.41421];
        yield 'sqrt(0)' => ['Math.sqrt(0)', 0];
        yield 'sqrt(-1)' => ['Math.sqrt(-1)', static::NAN];
        yield 'trunc(13.37)' => ['Math.trunc(13.37)', 13];
        yield 'trunc(-0.123)' => ['Math.trunc(-0.123)', 0];
        yield 'trunc("-1.123")' => ['Math.trunc("-1.123")', -1];
        yield 'trunc(0)' => ['Math.trunc(0)', 0];
        yield 'trunc(0.0)' => ['Math.trunc(0.0)', 0];
        yield 'trunc("foo")' => ['Math.trunc("foo")', static::NAN];
    }

    #[DataProvider('variousExamples')]
    #[Test]
    public function variousFunctionsWork($method, $expected): void
    {
        $helper = new MathHelper();
        $evaluator = new InterpretedEvaluator();
        $context = new Context([
            'Math' => $helper
        ]);
        $result = $evaluator->evaluate($method, $context);
        if ($expected === static::NAN) {
            self::assertNan($result, 'Expected NAN, got value "' . @(string)$result . '"');
        } else {
            self::assertEqualsWithDelta($expected, $result, 0.001, 'Rounded value did not match');
        }
    }

    public static function finiteAndNanExamples(): \Iterator
    {
        yield 'isFinite(42)' => ['isFinite', 42, true];
        yield 'isFinite(NAN)' => ['isFinite', NAN, false];
        yield 'isFinite(INF)' => ['isFinite', INF, false];
        yield 'isFinite("42")' => ['isFinite', '42', true];
        yield 'isFinite("foo")' => ['isFinite', 'foo', false];
        yield 'isInfinite(42)' => ['isInfinite', 42, false];
        yield 'isInfinite(NAN)' => ['isInfinite', NAN, false];
        yield 'isInfinite(INF)' => ['isInfinite', INF, true];
        yield 'isInfinite(-INF)' => ['isInfinite', -INF, true];
        yield 'isInfinite("42")' => ['isInfinite', '42', false];
        yield 'isInfinite("foo")' => ['isInfinite', 'foo', false];
        yield 'isNaN(42)' => ['isNaN', 42, false];
        yield 'isNaN(NAN)' => ['isNaN', NAN, true];
        yield 'isNaN("42")' => ['isNaN', '42', false];
        yield 'isNaN("foo")' => ['isNaN', 'foo', true];
        yield 'isNaN(INF)' => ['isNaN', INF, false];
    }

    #[DataProvider('finiteAndNanExamples')]
    #[Test]
    public function finiteAndNanFunctionsWork($method, $value, $expected): void
    {
        $helper = new MathHelper();
        $result = $helper->$method($value);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function randomReturnsARandomResultFromZeroToOneExclusive(): void
    {
        $helper = new MathHelper();
        $r1 = $helper->random();
        $atLeastOneRandomResult = false;
        for ($i = 0; $i < 100; $i++) {
            $ri = $helper->random();
            if ($ri !== $r1) {
                $atLeastOneRandomResult = true;
            }
            self::assertLessThan(1.0, $ri, 'Result should be less than 1');
            self::assertGreaterThanOrEqual(0.0, $ri, 'Result should be greater than 0');
        }
        self::assertTrue($atLeastOneRandomResult, 'random() should return a random result');
    }

    #[Test]
    public function randomIntReturnsARandomResultFromMinToMaxExclusive(): void
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
            self::assertLessThanOrEqual($max, $ri);
            self::assertGreaterThanOrEqual($min, $ri);
        }
        self::assertTrue($atLeastOneRandomResult, 'random() should return a random result');
    }
}
