<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Simple string helper class for Eel contexts
 */
class StringHelper {

	/**
	 * A wrapper for \substr().
	 *
	 * @param string $string
	 * @param integer $start
	 * @param integer $length
	 * @return string
	 */
	public function substr($string, $start, $length = NULL) {
		return substr($string, $start, $length);
	}

	/**
	 * A wrapper for \strip_tags().
	 *
	 * @param string $string
	 * @return string
	 */
	public function stripTags($string) {
		return strip_tags($string);
	}

	/**
	 * A wrapper for \trim(\strip_tags()).
	 *
	 * @param string $string
	 * @return string
	 */
	public function cleanup($string) {
		return trim(strip_tags($string));
	}

	/**
	 * Crops the input at the given position if needed and appends
	 * the given $ellipsis.
	 *
	 * Note: this method does not detect word-boundaries or is in any
	 * other way clever.
	 *
	 * @param string $string
	 * @param integer $limit
	 * @param string $ellipsis
	 * @return string
	 */
	public function crop($string, $limit, $ellipsis = '...') {
		if (strlen($string) > $limit) {
			return substr($string, 0, $limit) . $ellipsis;
		} else {
			return $string;
		}
	}

}
?>