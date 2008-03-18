<?php
declare(ENCODING = 'utf-8');

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
 * The array functions from the good old t3lib_div.
 *
 * @package		FLOW3
 * @subpackage	Utility
 * @version     $Id:T3_FLOW3_Utility_Arrays.php 467 2008-02-06 19:34:56Z robert $
 * @copyright   Copyright belongs to the respective authors
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @internal	(robert) I'm not sure yet if we should use this library statically or as a singleton. The latter might be problematic if we use it from the Core classes.
 */
class T3_FLOW3_Utility_Arrays {

	/**
	 * Explodes a $string delimited by $delimeter and passes each item in the array through intval().
	 * Corresponds to explode(), but with conversion to integers for all values.
	 *
	 * @param	string		$delimiter: Delimiter string to explode with
	 * @param	string		$string: The string to explode
	 * @return	array		Exploded values, all converted to integers
	 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
	 */
	public function integerExplode($delimiter, $string) {
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
	 * @param	string		$delimiter: Delimiter string to explode with
	 * @param	string		$string: The string to explode
	 * @param	boolean		$onlyNonEmptyValues: If set, all empty values (='') will NOT be set in output
	 * @return	array		Exploded values
	 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
	 */
	public function trimExplode($delimiter, $string, $onlyNonEmptyValues=FALSE) {
		$chunksArr = explode($delimiter, $string);
		$newChunksArr = array();
		while(list($key, $value) = each($chunksArr))	{
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
	 * @param	array		First array
	 * @param	array		Second array, overruling the first array
	 * @param	boolean		If set, keys that are NOT found in $firstArray (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @param	boolean		If set (which is the default), values from $secondArray will overrule if they are empty (according to PHP's empty() function)
	 * @return	array		Resulting array where $secondArray values has overruled $firstArray values
	 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
	 */
	public function arrayMergeRecursiveOverrule($firstArray, $secondArray, $dontAddNewKeys=FALSE, $emptyValuesOverride=TRUE) {
		if (!is_array($firstArray)) throw new InvalidArgumentException('$firstArray is not of type Array.', 1166719211, array($firstArray));
		if (!is_array($secondArray)) throw new InvalidArgumentException('$secondArray is not of type Array.', 1166719212, array($secondArray));

		reset($secondArray);
		while (list($key, $value) = each($secondArray)) {
			if (key_exists($key, $firstArray) && is_array($firstArray[$key])) {
				if (is_array($secondArray[$key]))	{
					$firstArray[$key] = self::arrayMergeRecursiveOverrule($firstArray[$key], $secondArray[$key], $dontAddNewKeys, $emptyValuesOverride);
				}
			} else {
				if ($dontAddNewKeys) {
					if (key_exists($key, $firstArray)) {
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
	 * @param  array			$array: Array to reorder
	 * @return array			The array with randomly ordered values
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function randomizeArrayOrder($array) {
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
}
?>