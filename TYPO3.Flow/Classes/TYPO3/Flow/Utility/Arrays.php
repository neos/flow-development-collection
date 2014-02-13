<?php
namespace TYPO3\Flow\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The array functions from the good old t3lib_div plus new code.
 *
 * @Flow\Scope("singleton")
 * @todo (robert) I'm not sure yet if we should use this library statically or as a singleton. The latter might be problematic if we use it from the Core classes.
 */
class Arrays {

	/**
	 * Explodes a $string delimited by $delimiter and passes each item in the array through intval().
	 * Corresponds to explode(), but with conversion to integers for all values.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @return array Exploded values, all converted to integers
	 */
	static public function integerExplode($delimiter, $string) {
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
	static public function trimExplode($delimiter, $string, $onlyNonEmptyValues = TRUE) {
		$chunksArr = explode($delimiter, $string);
		$newChunksArr = array();
		foreach ($chunksArr as $value) {
			if ($onlyNonEmptyValues === FALSE || strcmp('', trim($value))) {
				$newChunksArr[] = trim($value);
			}
		}
		reset($newChunksArr);
		return $newChunksArr;
	}

	/**
	 * Merges two arrays recursively and "binary safe" (integer keys are overridden as well), overruling similar values in the first array ($firstArray) with the values of the second array ($secondArray)
	 * In case of identical keys, ie. keeping the values of the second.
	 *
	 * @param array $firstArray First array
	 * @param array $secondArray Second array, overruling the first array
	 * @param boolean $dontAddNewKeys If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param boolean $emptyValuesOverride If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
	 * @return array Resulting array where $secondArray values has overruled $firstArray values
	 */
	static public function arrayMergeRecursiveOverrule(array $firstArray, array $secondArray, $dontAddNewKeys = FALSE, $emptyValuesOverride = TRUE) {
		$data = array(&$firstArray, $secondArray);
		$entryCount = 1;
		for ($i = 0; $i < $entryCount; $i++) {
			$firstArrayInner = &$data[$i * 2];
			$secondArrayInner = $data[$i * 2 + 1];
			foreach ($secondArrayInner as $key => $value) {
				$keyInFirstArray = array_key_exists($key, $firstArrayInner);
				if ($keyInFirstArray && is_array($firstArrayInner[$key])) {
					if ((!$emptyValuesOverride || $value !== array()) && is_array($value)) {
						$data[] = &$firstArrayInner[$key];
						$data[] = $value;
						$entryCount++;
					} else {
						$firstArrayInner[$key] = $value;
					}
				} else {
					if ($dontAddNewKeys) {
						if ($keyInFirstArray && ($emptyValuesOverride || !empty($value))) {
							$firstArrayInner[$key] = $value;
						}
					} else {
						if ($emptyValuesOverride || !empty($value)) {
							$firstArrayInner[$key] = $value;
						} elseif (!$keyInFirstArray && $value === array()) {
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
	 * Returns TRUE if the given array contains elements of varying types
	 *
	 * @param array $array
	 * @return boolean
	 */
	static public function containsMultipleTypes(array $array) {
		if (count($array) > 0) {
			reset($array);
			$previousType = gettype(current($array));
			next($array);
			while (list(, $value) = each($array)) {
				if ($previousType !== gettype($value)) {
					return TRUE;
				}
			}
		}
		return FALSE;
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
	static public function array_reduce(array $array, $function, $initial = NULL) {
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
	static public function getValueByPath(array &$array, $path) {
		if (is_string($path)) {
			$path = explode('.', $path);
		} elseif (!is_array($path)) {
			throw new \InvalidArgumentException('getValueByPath() expects $path to be string or array, "' . gettype($path) . '" given.', 1304950007);
		}
		$key = array_shift($path);
		if (isset($array[$key])) {
			if (count($path) > 0) {
				return (is_array($array[$key])) ? self::getValueByPath($array[$key], $path) : NULL;
			} else {
				return $array[$key];
			}
		} else {
			return NULL;
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
	static public function setValueByPath($subject, $path, $value) {
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
				$subject[$key] = array();
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
	static public function unsetValueByPath(array $array, $path) {
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
	static public function sortKeysRecursively(array &$array, $sortFlags = NULL) {
		foreach ($array as &$value) {
			if (is_array($value)) {
				if (self::sortKeysRecursively($value, $sortFlags) === FALSE) {
					return FALSE;
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
	static public function convertObjectToArray($subject) {
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
	static public function removeEmptyElementsRecursively(array $array) {
		$result = $array;
		foreach ($result as $key => $value) {
			if (is_array($value)) {
				$result[$key] = self::removeEmptyElementsRecursively($value);
				if ($result[$key] === array()) {
					unset($result[$key]);
				}
			} elseif ($value === NULL) {
				unset($result[$key]);
			}
		}
		return $result;
	}
}
