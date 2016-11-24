<?php
namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Some array functions to help with common tasks
 *
 */
abstract class Arrays
{
    /**
     * Explodes a $string delimited by $delimiter and passes each item in the array through intval().
     * Corresponds to explode(), but with conversion to integers for all values.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @return array Exploded values, all converted to integers
     */
    public static function integerExplode($delimiter, $string)
    {
        $chunksArr = explode($delimiter, $string);
        while (list($key, $value) = each($chunksArr)) {
            $chunks[$key] = intval($value);
        }
        reset($chunks);
        return $chunks;
    }

    /**
     * Explodes a string and trims all values for whitespace in the ends.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @param boolean $onlyNonEmptyValues If disabled, even empty values (='') will be set in output
     * @return array Exploded values
     */
    public static function trimExplode($delimiter, $string, $onlyNonEmptyValues = true)
    {
        $chunksArr = explode($delimiter, $string);
        $newChunksArr = [];
        foreach ($chunksArr as $value) {
            if ($onlyNonEmptyValues === false || strcmp('', trim($value))) {
                $newChunksArr[] = trim($value);
            }
        }
        reset($newChunksArr);
        return $newChunksArr;
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values
     * in the first array ($firstArray) with the values of the second array ($secondArray) in case of identical keys,
     * ie. keeping the values of the second.
     *
     * @param array $firstArray First array
     * @param array $secondArray Second array, overruling the first array
     * @param boolean $dontAddNewKeys If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
     * @param boolean $emptyValuesOverride If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
     * @return array Resulting array where $secondArray values has overruled $firstArray values
     */
    public static function arrayMergeRecursiveOverrule(array $firstArray, array $secondArray, $dontAddNewKeys = false, $emptyValuesOverride = true)
    {
        $data = [&$firstArray, $secondArray];
        $entryCount = 1;
        for ($i = 0; $i < $entryCount; $i++) {
            $firstArrayInner = &$data[$i * 2];
            $secondArrayInner = $data[$i * 2 + 1];
            foreach ($secondArrayInner as $key => $value) {
                if (isset($firstArrayInner[$key]) && is_array($firstArrayInner[$key])) {
                    if ((!$emptyValuesOverride || $value !== []) && is_array($value)) {
                        $data[] = &$firstArrayInner[$key];
                        $data[] = $value;
                        $entryCount++;
                    } else {
                        $firstArrayInner[$key] = $value;
                    }
                } else {
                    if ($dontAddNewKeys) {
                        if (array_key_exists($key, $firstArrayInner) && ($emptyValuesOverride || !empty($value))) {
                            $firstArrayInner[$key] = $value;
                        }
                    } else {
                        if ($emptyValuesOverride || !empty($value)) {
                            $firstArrayInner[$key] = $value;
                        } elseif (!isset($firstArrayInner[$key]) && $value === []) {
                            $firstArrayInner[$key] = $value;
                        }
                    }
                }
            }
        }
        reset($firstArray);
        return $firstArray;
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($firstArray) with the values of the second array ($secondArray)
     * In case of identical keys, ie. keeping the values of the second. The given $toArray closure will be used if one of the two array keys contains an array and the other not. It should return an array.
     *
     * @param array $firstArray First array
     * @param array $secondArray Second array, overruling the first array
     * @param callable $toArray The given closure will get a value that is not an array and has to return an array. This is to allow custom merging of simple types with (sub) arrays
     * @return array Resulting array where $secondArray values has overruled $firstArray values
     */
    public static function arrayMergeRecursiveOverruleWithCallback(array $firstArray, array $secondArray, \Closure $toArray)
    {
        $data = [&$firstArray, $secondArray];
        $entryCount = 1;
        for ($i = 0; $i < $entryCount; $i++) {
            $firstArrayInner = &$data[$i * 2];
            $secondArrayInner = $data[$i * 2 + 1];
            foreach ($secondArrayInner as $key => $value) {
                if (!isset($firstArrayInner[$key]) || (!is_array($firstArrayInner[$key]) && !is_array($value))) {
                    $firstArrayInner[$key] = $value;
                } else {
                    if (!is_array($value)) {
                        $value = $toArray($value);
                    }
                    if (!is_array($firstArrayInner[$key])) {
                        $firstArrayInner[$key] = $toArray($firstArrayInner[$key]);
                    }

                    if (is_array($firstArrayInner[$key]) && is_array($value)) {
                        $data[] = &$firstArrayInner[$key];
                        $data[] = $value;
                        $entryCount++;
                    } else {
                        $firstArrayInner[$key] = $value;
                    }
                }
            }
        }
        reset($firstArray);
        return $firstArray;
    }

    /**
     * Returns TRUE if the given array contains elements of varying types
     *
     * @param array $array
     * @return boolean
     */
    public static function containsMultipleTypes(array $array)
    {
        if (count($array) > 0) {
            reset($array);
            $previousType = gettype(current($array));
            next($array);
            while (list(, $value) = each($array)) {
                if ($previousType !== gettype($value)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Replacement for array_reduce that allows any type for $initial (instead
     * of only integer)
     *
     * @param array $array the array to reduce
     * @param string $function the reduce function with the same order of parameters as in the native array_reduce (i.e. accumulator first, then current array element)
     * @param mixed $initial the initial accumulator value
     * @return mixed
     */
    public static function array_reduce(array $array, $function, $initial = null)
    {
        $accumulator = $initial;
        foreach ($array as $value) {
            $accumulator = $function($accumulator, $value);
        }
        return $accumulator;
    }

    /**
     * Returns the value of a nested array by following the specifed path.
     *
     * @param array &$array The array to traverse as a reference
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @return mixed The value found, NULL if the path didn't exist (note there is no way to distinguish between a found NULL value and "path not found")
     * @throws \InvalidArgumentException
     */
    public static function getValueByPath(array &$array, $path)
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        } elseif (!is_array($path)) {
            throw new \InvalidArgumentException('getValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1304950007);
        }
        $key = array_shift($path);
        if (isset($array[$key])) {
            if (count($path) > 0) {
                return (is_array($array[$key])) ? self::getValueByPath($array[$key], $path) : null;
            } else {
                return $array[$key];
            }
        } else {
            return null;
        }
    }

    /**
     * Sets the given value in a nested array or object by following the specified path.
     *
     * @param array|\ArrayAccess $subject The array or ArrayAccess instance to work on
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @param mixed $value The value to set
     * @return array The modified array or object
     * @throws \InvalidArgumentException
     */
    public static function setValueByPath($subject, $path, $value)
    {
        if (!is_array($subject) && !($subject instanceof \ArrayAccess)) {
            throw new \InvalidArgumentException('setValueByPath() expects $subject to be array or an object implementing \ArrayAccess, "' . (is_object($subject) ? get_class($subject) : gettype($subject)) . '" given.', 1306424308);
        }
        if (is_string($path)) {
            $path = explode('.', $path);
        } elseif (!is_array($path)) {
            throw new \InvalidArgumentException('setValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1305111499);
        }
        $key = array_shift($path);
        if (count($path) === 0) {
            $subject[$key] = $value;
        } else {
            if (!isset($subject[$key]) || !is_array($subject[$key])) {
                $subject[$key] = [];
            }
            $subject[$key] = self::setValueByPath($subject[$key], $path, $value);
        }
        return $subject;
    }

    /**
     * Unsets an element/part of a nested array by following the specified path.
     *
     * @param array $array The array
     * @param array|string $path The path to follow. Either a simple array of keys or a string in the format 'foo.bar.baz'
     * @return array The modified array
     * @throws \InvalidArgumentException
     */
    public static function unsetValueByPath(array $array, $path)
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        } elseif (!is_array($path)) {
            throw new \InvalidArgumentException('unsetValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1305111513);
        }
        $key = array_shift($path);
        if (count($path) === 0) {
            unset($array[$key]);
        } else {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $array;
            }
            $array[$key] = self::unsetValueByPath($array[$key], $path);
        }
        return $array;
    }

    /**
     * Sorts multidimensional arrays by recursively calling ksort on its elements.
     *
     * @param array $array the array to sort
     * @param integer $sortFlags may be used to modify the sorting behavior using these values (see http://www.php.net/manual/en/function.sort.php)
     * @return boolean TRUE on success, FALSE on failure
     * @see asort()
     */
    public static function sortKeysRecursively(array &$array, $sortFlags = null)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                if (self::sortKeysRecursively($value, $sortFlags) === false) {
                    return false;
                }
            }
        }
        return ksort($array, $sortFlags);
    }

    /**
     * Recursively convert an object hierarchy into an associative array.
     *
     * @param mixed $subject An object or array of objects
     * @return array The subject represented as an array
     * @throws \InvalidArgumentException
     */
    public static function convertObjectToArray($subject)
    {
        if (!is_object($subject) && !is_array($subject)) {
            throw new \InvalidArgumentException('convertObjectToArray expects either array or object as input, ' . gettype($subject) . ' given.', 1287059709);
        }
        if (is_object($subject)) {
            $subject = (array)$subject;
        }
        foreach ($subject as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $subject[$key] = self::convertObjectToArray($value);
            }
        }
        return $subject;
    }

    /**
     * Recursively removes empty array elements.
     *
     * @param array $array
     * @return array the modified array
     */
    public static function removeEmptyElementsRecursively(array $array)
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::removeEmptyElementsRecursively($value);
                if ($result[$key] === []) {
                    unset($result[$key]);
                }
            } elseif ($value === null) {
                unset($result[$key]);
            }
        }
        return $result;
    }
}
