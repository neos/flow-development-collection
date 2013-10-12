<?php
namespace TYPO3\Flow\Utility\Unicode;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class with UTF-8 string functions, some inspired by what might be in some
 * future PHP version...
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Functions {

	/**
	 * Converts the first character of each word to uppercase and all remaining characters
	 * to lowercase.
	 *
	 * @param  string $str The string to convert
	 * @return string The converted string
	 * @api
	 */
	static public function strtotitle($str) {
		$result = '';
		$splitIntoLowerCaseWords = preg_split("/([\n\r\t ])/", self::strtolower($str), -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($splitIntoLowerCaseWords as $delimiterOrValue) {
			$result .= self::strtoupper(self::substr($delimiterOrValue, 0, 1)) . self::substr($delimiterOrValue, 1);
		}
		return $result;
	}

	/**
	 * PHP6 variant of substr()
	 *
	 * @param  string $string The string to crop
	 * @param  integer $start Position of the left boundary
	 * @param  integer $length (optional) Length of the returned string
	 * @return string The processed string
	 * @api
	 */
	static public function substr($string, $start, $length = NULL) {
		if ($length === 0) {
			return '';
		}

			// Cannot omit $len, when specifying charset
		if ($length === NULL) {
				// save internal encoding
			$enc = mb_internal_encoding();
			mb_internal_encoding('UTF-8');
			$str = mb_substr($string, $start);
				// restore internal encoding
			mb_internal_encoding($enc);

			return $str;
		} else {
			return mb_substr($string, $start, $length, 'UTF-8');
		}
	}

	/**
	 * PHP6 variant of strtoupper()
	 *
	 * @param  string $string The string to uppercase
	 * @return string The processed string
	 * @api
	 */
	static public function strtoupper($string) {
		return str_replace('ß', 'SS', mb_strtoupper($string, 'UTF-8'));
	}

	/**
	 * PHP6 variant of strtolower()
	 *
	 * @param  string $string The string to lowercase
	 * @return string The processed string
	 * @api
	 */
	static public function strtolower($string) {
		return mb_strtolower($string, 'UTF-8');
	}

	/**
	 * PHP6 variant of strlen() - assumes that the string is a Unicode string, not binary
	 *
	 * @param  string $string The string to count the characters of
	 * @return integer The number of characters
	 * @api
	 */
	static public function strlen($string) {
		return mb_strlen($string, 'UTF-8');
	}

	/**
	 * PHP6 variant of ucfirst() - assumes that the string is a Unicode string, not binary
	 *
	 * @param  string $string The string whose first letter should be uppercased
	 * @return string The same string, first character uppercased
	 * @api
	 */
	static public function ucfirst($string) {
		return self::strtoupper(self::substr($string, 0, 1)) . self::substr($string, 1);
	}

	/**
	 * PHP6 variant of lcfirst() - assumes that the string is a Unicode string, not binary
	 *
	 * @param  string $string The string whose first letter should be lowercased
	 * @return string The same string, first character lowercased
	 * @api
	 */
	static public function lcfirst($string) {
		return self::strtolower(self::substr($string, 0, 1)) . self::substr($string, 1);
	}

	/**
	 * PHP6 variant of strpos() - assumes that the string is a Unicode string, not binary
	 *
	 * @param string $haystack UTF-8 string to search in
	 * @param string $needle UTF-8 string to search for
	 * @param integer $offset Positition to start the search
	 * @return integer The character position
	 * @api
	 */
	static public function strpos($haystack, $needle, $offset = 0) {
		return mb_strpos($haystack, $needle, $offset, 'UTF-8');
	}

}
