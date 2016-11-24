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
    public function concat($array1, $array2, $array_ = null)
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
    public function join(array $array, $separator = ',')
    {
        return implode($separator, $array);
    }

    /**
     * Extract a portion of an indexed array
     *
     * @param array $array The array (with numeric indices)
     * @param string $begin
     * @param string $end
     * @return array
     */
    public function slice(array $array, $begin, $end = null)
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
    public function reverse(array $array)
    {
        return array_reverse($array);
    }

    /**
     * Get the array keys
     *
     * @param array $array The array
     * @return array
     */
    public function keys(array $array)
    {
        return array_keys($array);
    }

    /**
     * Get the length of an array
     *
     * @param array $array The array
     * @return integer
     */
    public function length(array $array)
    {
        return count($array);
    }

    /**
     * Check if an array is empty
     *
     * @param array $array The array
     * @return boolean TRUE if the array is empty
     */
    public function isEmpty(array $array)
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
     * @param array $array
     * @param mixed $searchElement
     * @param integer $fromIndex
     * @return mixed
     */
    public function indexOf(array $array, $searchElement, $fromIndex = null)
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
     * @return mixed A random entry or NULL if the array is empty
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
    public function sort(array $array)
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
     * When this option is set to FALSE, all keys will be replaced
     *
     * @param array $array
     * @param boolean $preserveKeys Wether to preserve the keys when shuffling the array
     * @return array The shuffled array
     */
    public function shuffle(array $array, $preserveKeys = true)
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
    public function pop(array $array)
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
    public function push(array $array, $element)
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
    public function shift(array $array)
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
    public function unshift(array $array, $element)
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
     * @param integer $offset Index of the first element to remove
     * @param integer $length Number of elements to remove
     * @param mixed $replacements Elements to insert instead of the removed range
     * @return array The array with removed and replaced elements
     */
    public function splice(array $array, $offset, $length = 1, $replacements = null)
    {
        $arguments = func_get_args();
        $replacements = array_slice($arguments, 3);
        array_splice($array, $offset, $length, $replacements);
        return $array;
    }

    /**
     *  Exchanges all keys with their associated values in an array
     *
     * Note that the values of array need to be valid keys, i.e. they need to be either integer or string.
     * If a value has several occurrences, the latest key will be used as its value, and all others will be lost.
     *
     * @param array $array
     * @return array The array with flipped keys and values
     */
    public function flip(array $array)
    {
        return array_flip($array);
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
