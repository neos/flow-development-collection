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
 * Array helpers for Eel contexts
 *
 * The implementation uses the JavaScript specificiation where applicable, including EcmaScript 6 proposals.
 *
 * See https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Array for a documentation and
 * specification of the JavaScript implementation.
 *
 * @Flow\Proxy(false)
 */
class ArrayHelper implements ProtectedContextAwareInterface
{

    /**
     * Concatenate arrays or values to a new array
     *
     * @param array|mixed $array1 First array or value
     * @param array|mixed $array2 Second array or value
     * @param array|mixed $array_ Optional variable list of additional arrays / values
     * @return array The array with concatenated arrays or values
     */
    public function concat($array1, $array2, $array_ = null): array
    {
        $arguments = func_get_args();
        foreach ($arguments as &$argument) {
            if (!is_array($argument)) {
                $argument = [$argument];
            }
        }
        return call_user_func_array('array_merge', $arguments);
    }

    /**
     * Join values of an array with a separator
     *
     * @param array $array Array with values to join
     * @param string $separator A separator for the values
     * @return string A string with the joined values separated by the separator
     */
    public function join(array $array, $separator = ','): string
    {
        return implode($separator, $array);
    }

    /**
     * Extract a portion of an indexed array
     *
     * @param array $array The array (with numeric indices)
     * @param int $begin
     * @param int $end
     * @return array
     */
    public function slice(array $array, $begin, $end = null): array
    {
        if ($end === null) {
            $end = count($array);
        } elseif ($end < 0) {
            $end = count($array) + $end;
        }
        $length = $end - $begin;
        return array_slice($array, $begin, $length);
    }

    /**
     * Returns an array in reverse order
     *
     * @param array $array The array
     * @return array
     */
    public function reverse(array $array): array
    {
        return array_reverse($array);
    }

    /**
     * Get the array keys
     *
     * @param array $array The array
     * @return array
     */
    public function keys(array $array): array
    {
        return array_keys($array);
    }

    /**
     * Get the length of an array
     *
     * @param array $array The array
     * @return int
     */
    public function length(array $array): int
    {
        return count($array);
    }

    /**
     * Check if an array is empty
     *
     * @param array $array The array
     * @return bool true if the array is empty
     */
    public function isEmpty(array $array): bool
    {
        return count($array) === 0;
    }

    /**
     * Get the first element of an array
     *
     * @param array $array The array
     * @return mixed
     */
    public function first(array $array)
    {
        return reset($array);
    }

    /**
     * Get the last element of an array
     *
     * @param array $array The array
     * @return mixed
     */
    public function last(array $array)
    {
        return end($array);
    }

    /**
     * Returns the first index at which a given element can be found in the array,
     * or -1 if it is not present
     *
     * @param array $array The array
     * @param mixed $searchElement The element value to find
     * @param int $fromIndex Position in the array to start the search.
     * @return int
     */
    public function indexOf(array $array, $searchElement, $fromIndex = null): int
    {
        if ($fromIndex !== null) {
            $array = array_slice($array, $fromIndex, null, true);
        }
        $result = array_search($searchElement, $array, true);
        if ($result === false) {
            return -1;
        }
        return $result;
    }

    /**
     * Picks a random element from the array
     *
     * @param array $array
     * @return mixed A random entry or null if the array is empty
     */
    public function random(array $array)
    {
        if ($array === []) {
            return null;
        }
        $randomIndex = array_rand($array);
        return $array[$randomIndex];
    }

    /**
     * Sorts an array
     *
     * The sorting is done first by numbers, then by characters.
     *
     * Internally natsort() is used as it most closely resembles javascript's sort().
     * Because there are no real associative arrays in Javascript, keys of the array will be preserved.
     *
     * @param array $array
     * @return array The sorted array
     */
    public function sort(array $array): array
    {
        if ($array === []) {
            return $array;
        }
        natsort($array);
        $i = 0;
        $newArray = [];
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $newArray[$key] = $value;
            } else {
                $newArray[$i] = $value;
                $i++;
            }
        }
        return $newArray;
    }

    /**
     * Shuffle an array
     *
     * Randomizes entries an array with the option to preserve the existing keys.
     * When this option is set to false, all keys will be replaced
     *
     * @param array $array
     * @param bool $preserveKeys Wether to preserve the keys when shuffling the array
     * @return array The shuffled array
     */
    public function shuffle(array $array, $preserveKeys = true): array
    {
        if ($array === []) {
            return $array;
        }
        if ($preserveKeys) {
            $keys = array_keys($array);
            shuffle($keys);
            $shuffledArray = [];
            foreach ($keys as $key) {
                $shuffledArray[$key] = $array[$key];
            }
            $array = $shuffledArray;
        } else {
            shuffle($array);
        }
        return $array;
    }

    /**
     * Removes the last element from an array
     *
     * Note: This differs from the JavaScript behavior of Array.pop which will return the popped element.
     *
     * An empty array will result in an empty array again.
     *
     * @param array $array
     * @return array The array without the last element
     */
    public function pop(array $array): array
    {
        if ($array === []) {
            return $array;
        }
        array_pop($array);
        return $array;
    }

    /**
     * Insert one or more elements at the end of an array
     *
     * Allows to push multiple elements at once::
     *
     *     Array.push(array, e1, e2)
     *
     * @param array $array
     * @param mixed $element
     * @return array The array with the inserted elements
     */
    public function push(array $array, $element): array
    {
        $elements = func_get_args();
        array_shift($elements);
        foreach ($elements as $element) {
            array_push($array, $element);
        }
        return $array;
    }

    /**
     * Remove the first element of an array
     *
     * Note: This differs from the JavaScript behavior of Array.shift which will return the shifted element.
     *
     * An empty array will result in an empty array again.
     *
     * @param array $array
     * @return array The array without the first element
     */
    public function shift(array $array): array
    {
        array_shift($array);
        return $array;
    }

    /**
     * Insert one or more elements at the beginning of an array
     *
     * Allows to insert multiple elements at once::
     *
     *     Array.unshift(array, e1, e2)
     *
     * @param array $array
     * @param mixed $element
     * @return array The array with the inserted elements
     */
    public function unshift(array $array, $element): array
    {
        // get all elements that are supposed to be added
        $elements = func_get_args();
        array_shift($elements);
        foreach ($elements as $element) {
            array_unshift($array, $element);
        }
        return $array;
    }

    /**
     * Replaces a range of an array by the given replacements
     *
     * Allows to give multiple replacements at once::
     *
     *     Array.splice(array, 3, 2, 'a', 'b')
     *
     * @param array $array
     * @param int $offset Index of the first element to remove
     * @param int $length Number of elements to remove
     * @param mixed $replacements Elements to insert instead of the removed range
     * @return array The array with removed and replaced elements
     */
    public function splice(array $array, $offset, $length = 1, $replacements = null): array
    {
        $arguments = func_get_args();
        $replacements = array_slice($arguments, 3);
        array_splice($array, $offset, $length, $replacements);
        return $array;
    }

    /**
     *  Exchanges all keys with their associated values in an array
     *
     * Note that the values of array need to be valid keys, i.e. they need to be either int or string.
     * If a value has several occurrences, the latest key will be used as its value, and all others will be lost.
     *
     * @param array $array
     * @return array The array with flipped keys and values
     */
    public function flip(array $array): array
    {
        return array_flip($array);
    }

    /**
     * Create an array containing a range of elements
     *
     * If a step value is given, it will be used as the increment between elements in the sequence.
     * step should be given as a positive number. If not specified, step will default to 1.
     *
     * @param mixed $start First value of the sequence.
     * @param mixed $end The sequence is ended upon reaching the end value.
     * @param int $step The increment between items, will default to 1.
     * @return array Array of elements from start to end, inclusive.
     */
    public function range($start, $end, $step = 1): array
    {
        return range($start, $end, $step);
    }

    /**
     * Set the specified key in the the array
     *
     * @param array $array
     * @param string|integer $key the key that should be set
     * @param mixed $value the value to assign to the key
     * @return array The modified array.
     */
    public function set(array $array, $key, $value): array
    {
        $array[$key] = $value;
        return $array;
    }

    /**
     * Apply the callback to each element of the array, passing each element and key as arguments
     *
     * Examples::
     *
     *     Array.map([1, 2, 3, 4], x => x * x)
     *     Array.map([1, 2, 3, 4], (x, index) => x * index)
     *
     * @param array $array Array of elements to map
     * @param callable $callback Callback to apply for each element, current value and key will be passed as arguments
     * @return array The array with callback applied, keys will be preserved
     */
    public function map(array $array, callable $callback): array
    {
        $result = [];
        foreach ($array as $key => $element) {
            $result[$key] = $callback($element, $key);
        }
        return $result;
    }

    /**
     * Apply the callback to each element of the array and accumulate a single value
     *
     * Examples::
     *
     *     Array.reduce([1, 2, 3, 4], (accumulator, currentValue) => accumulator + currentValue) // == 10
     *     Array.reduce([1, 2, 3, 4], (accumulator, currentValue) => accumulator + currentValue, 1) // == 11
     *
     * @param array $array Array of elements to reduce to a value
     * @param callable $callback Callback for accumulating values, accumulator, current value and key will be passed as arguments
     * @param mixed $initialValue Initial value, defaults to first item in array and callback starts with second entry
     * @return mixed
     */
    public function reduce(array $array, callable $callback, $initialValue = null)
    {
        if ($initialValue !== null) {
            $accumulator = $initialValue;
        } else {
            $accumulator = array_shift($array);
        }
        foreach ($array as $key => $element) {
            $accumulator = $callback($accumulator, $element, $key);
        }
        return $accumulator;
    }

    /**
     * Filter an array by a test given as the callback, passing each element and key as arguments
     *
     * Examples:
     *
     *     Array.filter([1, 2, 3, 4], x => x % 2 == 0) // == [2, 4]
     *     Array.filter(['foo', 'bar', 'baz'], (x, index) => index < 2) // == ['foo', 'bar']
     *
     * @param array $array Array of elements to filter
     * @param callable $callback Callback for testing if an element should be included in the result, current value and key will be passed as arguments
     * @return array The array with elements where callback returned true
     */
    public function filter(array $array, callable $callback = null): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Check if at least one element in an array passes a test given by the calback,
     * passing each element and key as arguments
     *
     * Example::
     *
     *     Array.some([1, 2, 3, 4], x => x % 2 == 0) // == true
     *     Array.some([1, 2, 3, 4], x => x > 4) // == false
     *
     * @param array $array Array of elements to test
     * @param callable $callback Callback for testing elements, current value and key will be passed as arguments
     * @return bool True if at least one element passed the test
     */
    public function some(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all elements in an array pass a test given by the calback,
     * passing each element and key as arguments
     *
     * Example::
     *
     *     Array.every([1, 2, 3, 4], x => x % 2 == 0) // == false
     *     Array.every([2, 4, 6, 8], x => x % 2) // == true
     *
     * @param array $array Array of elements to test
     * @param callable $callback Callback for testing elements, current value and key will be passed as arguments
     * @return bool True if all elements passed the test
     */
    public function every(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
