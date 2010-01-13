<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The array functions from the good old t3lib_div plus new code.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @todo (robert) I'm not sure yet if we should use this library statically or as a singleton. The latter might be problematic if we use it from the Core classes.
 */
class Arrays {

	/**
	 * Explodes a $string delimited by $delimeter and passes each item in the array through intval().
	 * Corresponds to explode(), but with conversion to integers for all values.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @return array Exploded values, all converted to integers
	 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
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
	 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
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
	 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
	 */
	static public function arrayMergeRecursiveOverrule(array $firstArray, array $secondArray, $dontAddNewKeys = FALSE, $emptyValuesOverride = TRUE) {
		reset($secondArray);
		while (list($key, $value) = each($secondArray)) {
			if (array_key_exists($key, $firstArray) && is_array($firstArray[$key])) {
				if (is_array($secondArray[$key])) {
					$firstArray[$key] = self::arrayMergeRecursiveOverrule($firstArray[$key], $secondArray[$key], $dontAddNewKeys, $emptyValuesOverride);
				}
			} else {
				if ($dontAddNewKeys) {
					if (array_key_exists($key, $firstArray)) {
						if ($emptyValuesOverride || !empty($value)) {
							$firstArray[$key] = $value;
						}
					}
				} else {
					if ($emptyValuesOverride || !empty($value)) {
						$firstArray[$key] = $value;
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function array_reduce(array $array, $function, $initial = NULL) {
		$accumlator = $initial;
		foreach($array as $value) {
			$accumlator = $function($accumlator, $value);
		}
		return $accumlator;
	}

	/**
	 * Returns the value of a nested array by following the specifed path.
	 *
	 * @param array $array The array to traverse
	 * @param array $path The path to follow, ie. a simple array of keys
	 * @return mixed The value found, NULL if the path didn't exist
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function getValueByPath(array $array, array $path) {
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
	 * Sets the given value in a nested array by following the specifed path.
	 *
	 * @param array $array The array
	 * @param array $path The path to follow, ie. a simple array of keys
	 * @param mixed $value The value to set
	 * @return array The modified array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function setValueByPath(array $array, array $path, $value) {
		$key = array_shift($path);
		if (count($path) === 0) {
			$array[$key] = $value;
		} else {
			if (!isset($array[$key]) || !is_array($array[$key])) {
				$array[$key] = array();
			}
			$array[$key] = self::setValueByPath($array[$key], $path, $value);
		}
		return $array;
	}

	/**
	 * Sorts multidimensional arrays by recursively calling ksort on its elements.
	 *
	 * @param array $array the array to sort
	 * @param integer $sortFlags may be used to modify the sorting behavior using these values (see http://www.php.net/manual/en/function.sort.php)
	 * @return boolean TRUE on success, FALSE on failure
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see asort()
	 */
	static public function sortKeysRecursively(array &$array, $sortFlags = NULL) {
		foreach($array as &$value) {
			if (is_array($value)) {
				if (self::sortKeysRecursively($value, $sortFlags) === FALSE) {
					return FALSE;
				}
			}
		}
		return ksort($array, $sortFlags);
	}

}
?>