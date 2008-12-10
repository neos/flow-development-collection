<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Utility
 * @version $Id:\F3\FLOW3\Utility\Arrays.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * The array functions from the good old t3lib_div plus new code.
 *
 * @package FLOW3
 * @subpackage Utility
 * @version $Id:\F3\FLOW3\Utility\Arrays.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @internal (robert) I'm not sure yet if we should use this library statically or as a singleton. The latter might be problematic if we use it from the Core classes.
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
	 * @param boolean $onlyNonEmptyValues If set, all empty values (='') will NOT be set in output
	 * @return array Exploded values
	 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
	 */
	static public function trimExplode($delimiter, $string, $onlyNonEmptyValues = FALSE) {
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
	 * @param array First array
	 * @param array Second array, overruling the first array
	 * @param boolean If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param boolean If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
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
	 * Randomizes the order of array values. The array should not be an associative array
	 * as the key-value relations will be lost.
	 *
	 * @param array $array Array to reorder
	 * @return array The array with randomly ordered values
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function randomizeArrayOrder(array $array) {
		$reorderedArray = array();
		if (count($array) > 1) {
			$keysInRandomOrder = array_rand($array, count($array));
			foreach ($keysInRandomOrder as $key) {
				$reorderedArray[] = $array[$key];
			}
		} else {
			$reorderedArray = $array;
		}
		return $reorderedArray;
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

}
?>