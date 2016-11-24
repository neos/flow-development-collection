<?php
namespace Neos\Eel\Helper;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Math helpers for Eel contexts
 *
 * The implementation sticks to the JavaScript specificiation including EcmaScript 6 proposals.
 *
 * See https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Math for a documentation and
 * specification of the JavaScript implementation.
 *
 * @Flow\Proxy(false)
 */
class MathHelper implements ProtectedContextAwareInterface
{
    /**
     * @return float Euler's constant and the base of natural logarithms, approximately 2.718
     */
    public function getE()
    {
        return exp(1);
    }

    /**
     * @return float Natural logarithm of 2, approximately 0.693
     */
    public function getLN2()
    {
        return log(2);
    }

    /**
     * @return float Natural logarithm of 10, approximately 2.303
     */
    public function getLN10()
    {
        return log(10);
    }

    /**
     * @return float Base 2 logarithm of E, approximately 1.443
     */
    public function getLOG2E()
    {
        return log(exp(1), 2);
    }

    /**
     * @return float Base 10 logarithm of E, approximately 0.434
     */
    public function getLOG10E()
    {
        return log(exp(1), 10);
    }

    /**
     * @return float Ratio of the circumference of a circle to its diameter, approximately 3.14159
     */
    public function getPI()
    {
        return pi();
    }

    /**
     * @return float Square root of 1/2; equivalently, 1 over the square root of 2, approximately 0.707
     */
    public function getSQRT1_2()
    {
        return sqrt(0.5);
    }

    /**
     * @return float Square root of 2, approximately 1.414
     */
    public function getSQRT2()
    {
        return sqrt(2);
    }

    /**
     * @param float $x A number
     * @return float The absolute value of the given value
     */
    public function abs($x = 'NAN')
    {
        if (!is_numeric($x) && $x !== null) {
            return NAN;
        }
        return abs($x);
    }

    /**
     * @param float $x A number
     * @return float The arccosine (in radians) of the given value
     */
    public function acos($x)
    {
        return acos($x);
    }

    /**
     * @param float $x A number
     * @return float The hyperbolic arccosine (in radians) of the given value
     */
    public function acosh($x)
    {
        return acosh($x);
    }

    /**
     * @param float $x A number
     * @return float The arcsine (in radians) of the given value
     */
    public function asin($x)
    {
        return asin($x);
    }

    /**
     * @param float $x A number
     * @return float The hyperbolic arcsine (in radians) of the given value
     */
    public function asinh($x)
    {
        return asinh($x);
    }

    /**
     * @param float $x A number
     * @return float The arctangent (in radians) of the given value
     */
    public function atan($x)
    {
        return atan($x);
    }

    /**
     * @param float $x A number
     * @return float The hyperbolic arctangent (in radians) of the given value
     */
    public function atanh($x)
    {
        return atanh($x);
    }

    /**
     * @param float $y A number
     * @param float $x A number
     * @return float The arctangent of the quotient of its arguments
     */
    public function atan2($y, $x)
    {
        return atan2($y, $x);
    }

    /**
     * @param float $x A number
     * @return float The cube root of the given value
     */
    public function cbrt($x)
    {
        $y = pow(abs($x), 1 / 3);
        return $x < 0 ? -$y : $y;
    }

    /**
     * @param float $x A number
     * @return float The smallest integer greater than or equal to the given value
     */
    public function ceil($x)
    {
        return ceil($x);
    }

    /**
     * @param float $x A number given in radians
     * @return float The cosine of the given value
     */
    public function cos($x)
    {
        return cos($x);
    }

    /**
     * @param float $x A number
     * @return float The hyperbolic cosine of the given value
     */
    public function cosh($x)
    {
        return cosh($x);
    }

    /**
     * @param float $x A number
     * @return float The power of the Euler's constant with the given value (e^x)
     */
    public function exp($x)
    {
        return exp($x);
    }

    /**
     * @param float $x A number
     * @return float The power of the Euler's constant with the given value minus 1 (e^x - 1)
     */
    public function expm1($x)
    {
        return expm1($x);
    }

    /**
     * @param float $x A number
     * @return float The largest integer less than or equal to the given value
     */
    public function floor($x)
    {
        return floor($x);
    }

    /**
     * Test if the given value is a finite number
     *
     * This is equivalent to the global isFinite() function in JavaScript.
     *
     * @param mixed $x A value
     * @return boolean TRUE if the value is a finite (not NAN) number
     */
    public function isFinite($x)
    {
        return is_numeric($x) && is_finite($x);
    }

    /**
     * Test if the given value is an infinite number (INF or -INF)
     *
     * This function has no direct equivalent in JavaScript.
     *
     * @param mixed $x A value
     * @return boolean TRUE if the value is INF or -INF
     */
    public function isInfinite($x)
    {
        return is_numeric($x) && is_infinite($x);
    }

    /**
     * Test if the given value is not a number (either not numeric or NAN)
     *
     * This is equivalent to the global isNaN() function in JavaScript.
     *
     * @param mixed $x A value
     * @return boolean TRUE if the value is not a number
     */
    public function isNaN($x)
    {
        return !is_numeric($x) || is_nan($x);
    }

    /**
     * @param float $x A number
     * @param float $y A number
     * @param float $z_ Optional variable list of additional numbers
     * @return float The square root of the sum of squares of the arguments
     */
    public function hypot($x, $y, $z_ = null)
    {
        if ($z_ === null) {
            return hypot($x, $y);
        }
        $sum = 0;
        foreach (func_get_args() as $value) {
            $sum += $value * $value;
        }
        return sqrt($sum);
    }

    /**
     * @param float $x A number
     * @return float The natural logarithm (base e) of the given value
     */
    public function log($x)
    {
        return log($x);
    }

    /**
     * @param float $x A number
     * @return float The natural logarithm (base e) of 1 + the given value
     */
    public function log1p($x)
    {
        return log1p($x);
    }

    /**
     * @param float $x A number
     * @return float The base 10 logarithm of the given value
     */
    public function log10($x)
    {
        return log10($x);
    }

    /**
     * @param float $x A number
     * @return float The base 2 logarithm of the given value
     */
    public function log2($x)
    {
        return log($x, 2);
    }

    /**
     * @param float $x A number
     * @param float $y_ Optional variable list of additional numbers
     * @return float The largest of the given numbers (zero or more)
     */
    public function max($x = null, $y_ = null)
    {
        $arguments = func_get_args();
        if ($arguments !== []) {
            return call_user_func_array('max', func_get_args());
        } else {
            return -INF;
        }
    }

    /**
     * @param float $x A number
     * @param float $y_ Optional variable list of additional numbers
     * @return float The smallest of the given numbers (zero or more)
     */
    public function min($x = null, $y_ = null)
    {
        $arguments = func_get_args();
        if ($arguments !== []) {
            return call_user_func_array('min', func_get_args());
        } else {
            return INF;
        }
    }

    /**
     * Calculate the power of x by y
     *
     * @param float $x The base
     * @param float $y The exponent
     * @return float The base to the exponent power (x^y)
     */
    public function pow($x, $y)
    {
        return pow($x, $y);
    }

    /**
     * Get a random foating point number between 0 (inclusive) and 1 (exclusive)
     *
     * That means a result will always be less than 1 and greater or equal to 0, the same way Math.random() works in
     * JavaScript.
     *
     * See Math.randomInt(min, max) for a function that returns random integer numbers from a given interval.
     *
     * @return float A random floating point number between 0 (inclusive) and 1 (exclusive), that is from [0, 1)
     */
    public function random()
    {
        return mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
    }

    /**
     * Get a random integer number between a min and max value (inclusive)
     *
     * That means a result will always be greater than or equal to min and less than or equal to max.
     *
     * @param integer $min The lower bound for the random number (inclusive)
     * @param integer $max The upper bound for the random number (inclusive)
     * @return integer A random number between min and max (inclusive), that is from [min, max]
     */
    public function randomInt($min, $max)
    {
        return mt_rand($min, $max);
    }

    /**
     * Rounds the subject to the given precision
     *
     * The precision defines the number of digits after the decimal point.
     * Negative values are also supported (-1 rounds to full 10ths).
     *
     * @param float $subject The value to round
     * @param integer $precision The precision (digits after decimal point) to use, defaults to 0
     * @return float The rounded value
     */
    public function round($subject, $precision = 0)
    {
        if (!is_numeric($subject)) {
            return NAN;
        }
        $subject = floatval($subject);
        if ($precision != null && !is_int($precision)) {
            return NAN;
        }
        return round($subject, $precision);
    }

    /**
     * Get the sign of the given number, indicating whether the number is positive, negative or zero
     *
     * @param integer|float $x The value
     * @return integer -1, 0, 1 depending on the sign or NAN if the given value was not numeric
     */
    public function sign($x)
    {
        if ($x < 0) {
            return -1;
        } elseif ($x > 0) {
            return 1;
        } elseif ($x === 0 || $x === 0.0) {
            return 0;
        } else {
            return NAN;
        }
    }

    /**
     * @param float $x A number given in radians
     * @return float The sine of the given value
     */
    public function sin($x)
    {
        return sin($x);
    }

    /**
     * @param float $x A number
     * @return float The hyperbolic sine of the given value
     */
    public function sinh($x)
    {
        return sinh($x);
    }

    /**
     * @param float $x A number
     * @return float The square root of the given number
     */
    public function sqrt($x)
    {
        return sqrt($x);
    }

    /**
     * @param float $x A number given in radians
     * @return float The tangent of the given value
     */
    public function tan($x)
    {
        return tan($x);
    }

    /**
     * @param float $x A number
     * @return float The hyperbolic tangent of the given value
     */
    public function tanh($x)
    {
        return tanh($x);
    }

    /**
     * Get the integral part of the given number by removing any fractional digits
     *
     * This function doesn't round the given number but merely calls ceil(x) or floor(x) depending
     * on the sign of the number.
     *
     * @param float $x A number
     * @return integer The integral part of the given number
     */
    public function trunc($x)
    {
        $sign = $this->sign($x);
        switch ($sign) {
            case -1:
                return ceil($x);
            case 1:
                return floor($x);
            default:
                return $sign;
        }
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
