<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Array helpers for Eel contexts
 */
class ArrayHelper {

	/**
	 * Concatenate arrays or values to a new array
	 *
	 * @param array|mixed $array1 First array or value
	 * @param array|mixed $array2 Second array or value
	 * @param array|mixed $array_ Optional variable list of additional arrays / values
	 * @return array The array with concatenated arrays or values
	 */
	public function concat($array1, $array2, $array_ = NULL) {
		$arguments = func_get_args();
		foreach ($arguments as &$argument) {
			if (!is_array($argument)) {
				$argument = array($argument);
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
	public function join(array $array, $separator = ',') {
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
	public function slice(array $array, $begin, $end = NULL) {
		if ($end === NULL) {
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
	public function reverse(array $array) {
		return array_reverse($array);
	}

	/**
	 * Get the array keys
	 *
	 * @param array $array The array
	 * @return array
	 */
	public function keys(array $array) {
		return array_keys($array);
	}

	/**
	 * Get the length of an array
	 *
	 * @param array $array The array
	 * @return integer
	 */
	public function length(array $array) {
		return count($array);
	}

	/**
	 * Check if an array is empty
	 *
	 * @param array $array The array
	 * @return boolean TRUE if the array is empty
	 */
	public function isEmpty(array $array) {
		return count($array) === 0;
	}

	/**
	 * Get the first element of an array
	 *
	 * @param array $array The array
	 * @return mixed
	 */
	public function first(array $array) {
		return reset($array);
	}

	/**
	 * Get the last element of an array
	 *
	 * @param array $array The array
	 * @return mixed
	 */
	public function last(array $array) {
		return end($array);
	}

	/**
	 * @param array $array
	 * @param mixed $searchElement
	 * @param integer $fromIndex
	 * @return mixed
	 */
	public function indexOf(array $array, $searchElement, $fromIndex = NULL) {
		if ($fromIndex !== NULL) {
			$array = array_slice($array, $fromIndex, NULL, TRUE);
		}
		$result = array_search($searchElement, $array, TRUE);
		if ($result === FALSE) {
			return -1;
		}
		return $result;
	}

}
?>