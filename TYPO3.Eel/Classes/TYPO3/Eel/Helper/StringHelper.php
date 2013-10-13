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

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * String helpers for Eel contexts
 */
class StringHelper implements ProtectedContextAwareInterface {

	/**
	 * Return the characters in a string from start up to the given length
	 *
	 * This implementation follows the JavaScript specification for "substr".
	 *
	 * Examples:
	 *
	 *   String.substr('Hello, World!', 7, 5) === 'World'
	 *
	 *   String.substr('Hello, World!', 7) === 'World!'
	 *
	 *   String.substr('Hello, World!', -6) === 'World!'
	 *
	 * @param string $string A string
	 * @param integer $start Start offset
	 * @param integer $length Maximum length of the substring that is returned
	 * @return string The substring
	 */
	public function substr($string, $start, $length = NULL) {
		if ($length === NULL) {
			$length = mb_strlen($string, 'UTF-8');
		}
		$length = max(0, $length);
		return mb_substr($string, $start, $length, 'UTF-8');
	}

	/**
	 * Return the characters in a string from a start index to an end index
	 *
	 * This implementation follows the JavaScript specification for "substring".
	 *
	 * Examples:
	 *
	 *   String.substring('Hello, World!', 7, 12) === 'World'
	 *
	 *   String.substring('Hello, World!', 7) === 'World!'
	 *
	 * @param string $string
	 * @param integer $start Start index
	 * @param integer $end End index
	 * @return string The substring
	 */
	public function substring($string, $start, $end = NULL) {
		if ($end === NULL) {
			$end = mb_strlen($string, 'UTF-8');
		}
		$start = max(0, $start);
		$end = max(0, $end);
		if ($start > $end) {
			$temp = $start;
			$start = $end;
			$end = $temp;
		}
		$length = $end - $start;
		return mb_substr($string, $start, $length, 'UTF-8');
	}

	/**
	 * @param string $string
	 * @param integer $index
	 * @return string The character at the given index
	 */
	public function charAt($string, $index) {
		if ($index < 0) {
			return '';
		}
		return mb_substr($string, $index, 1, 'UTF-8');
	}

	/**
	 * Test if a string ends with the given search string
	 *
	 * Examples:
	 *
	 *   String.endsWith('Hello, World!', 'World!') === true
	 *
	 * @param string $string The string
	 * @param string $search A string to search
	 * @param integer $position Optional position for limiting the string
	 * @return boolean TRUE if the string ends with the given search
	 */
	public function endsWith($string, $search, $position = NULL) {
		$position = $position !== NULL ? $position : mb_strlen($string, 'UTF-8');
		$position = $position - mb_strlen($search, 'UTF-8');
		return mb_strrpos($string, $search, NULL, 'UTF-8') === $position;
	}

	/**
	 * @param string $string
	 * @param string $search
	 * @param integer $fromIndex
	 * @return integer
	 */
	public function indexOf($string, $search, $fromIndex = NULL) {
		$fromIndex = max(0, $fromIndex);
		if ($search === '') {
			return min(mb_strlen($string, 'UTF-8'), $fromIndex);
		}
		$index = mb_strpos($string, $search, $fromIndex, 'UTF-8');
		if ($index === FALSE) {
			return -1;
		}
		return (integer)$index;
	}

	/**
	 * @param string $string
	 * @param string $search
	 * @param integer $toIndex
	 * @return integer
	 */
	public function lastIndexOf($string, $search, $toIndex = NULL) {
		$length = mb_strlen($string, 'UTF-8');
		if ($toIndex === NULL) {
			$toIndex = $length;
		}
		$toIndex = max(0, $toIndex);
		if ($search === '') {
			return min($length, $toIndex);
		}
		$string = mb_substr($string, 0, $toIndex, 'UTF-8');
		$index = mb_strrpos($string, $search, 0, 'UTF-8');
		if ($index === FALSE) {
			return -1;
		}
		return (integer)$index;
	}

	/**
	 * Match a string with a regular expression (PREG style)
	 *
	 * @param string $string
	 * @param string $pattern
	 * @return array The matches as array or NULL if not matched
	 * @throws \TYPO3\Eel\EvaluationException
	 */
	public function match($string, $pattern) {
		$number = preg_match($pattern, $string, $matches);
		if ($number === FALSE) {
			throw new \TYPO3\Eel\EvaluationException('Error evaluating regular expression ' . $pattern . ': ' . preg_last_error(), 1372793595);
		}
		if ($number === 0) {
			return NULL;
		}
		return $matches;
	}

	/**
	 * Replace occurences of a search string inside the string
	 *
	 * Note: this method does not perform regular expression matching.
	 *
	 * @param string $string
	 * @param string $search
	 * @param string $replace
	 * @return string The string with all occurences replaced
	 */
	public function replace($string, $search, $replace) {
		return str_replace($search, $replace, $string);
	}

	/**
	 * Split a string by a separator
	 *
	 * Node: This implementation follows JavaScript semantics without support of regular expressions.
	 *
	 * @param string $string
	 * @param string $separator
	 * @param integer $limit
	 * @return array
	 */
	public function split($string, $separator = NULL, $limit = NULL) {
		if ($separator === NULL) {
			return array($string);
		}
		if ($separator === '') {
			$result = str_split($string);
			if ($limit !== NULL) {
				$result = array_slice($result, 0, $limit);
			}
			return $result;
		}
		if ($limit === NULL) {
			$result = explode($separator, $string);
		} else {
			$result = explode($separator, $string, $limit);
		}
		return $result;
	}

	/**
	 * Test if a string starts with the given search string
	 *
	 * @param string $string
	 * @param string $search
	 * @param integer $position
	 * @return boolean
	 */
	public function startsWith($string, $search, $position = NULL) {
		$position = $position !== NULL ? $position : 0;
		return mb_strrpos($string, $search, NULL, 'UTF-8') === $position;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function toLowerCase($string) {
		return mb_strtolower($string, 'UTF-8');
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function toUpperCase($string) {
		return mb_strtoupper($string, 'UTF-8');
	}

	/**
	 * Strip all tags from the given string
	 *
	 * This is a wrapper for the strip_tags() PHP function.
	 *
	 * @param string $string
	 * @return string
	 */
	public function stripTags($string) {
		return strip_tags($string);
	}

	/**
	 * Test if the given string is blank (empty or consists of whitespace only)
	 *
	 * @param string $string
	 * @return boolean TRUE if the given string is blank
	 */
	public function isBlank($string) {
		return trim((string)$string) === '';
	}

	/**
	 * Trim whitespace at the beginning and end of a string
	 *
	 *
	 *
	 * @param string $string
	 * @param string $charlist Optional list of characters that should be trimmed, defaults to whitespace
	 * @return string
	 */
	public function trim($string, $charlist = NULL) {
		if ($charlist === NULL) {
			return trim($string);
		} else {
			return trim($string, $charlist);
		}
	}

	/**
	 * Convert a string to integer
	 *
	 * @param string $string
	 * @return string
	 */
	public function toInteger($string) {
		return (integer)$string;
	}

	/**
	 * Convert a string to float
	 *
	 * @param string $string
	 * @return string
	 */
	public function toFloat($string) {
		return (float)$string;
	}

	/**
	 * Convert a string to boolean
	 *
	 * A value is TRUE, if it is either the string "TRUE" or "true" or the number "1".
	 *
	 * @param string $string
	 * @return string
	 */
	public function toBoolean($string) {
		return strtolower($string) === 'true' || (integer)$string === 1;
	}

	/**
	 * Encode the string for URLs according to RFC 3986
	 *
	 * @param string $string
	 * @return string
	 */
	public function rawUrlEncode($string) {
		return rawurlencode($string);
	}

	/**
	 * Decode the string from URLs according to RFC 3986
	 *
	 * @param string $string
	 * @return string
	 */
	public function rawUrlDecode($string) {
		return rawurldecode($string);
	}

	/**
	 * @param string $string
	 * @param boolean $preserveEntities TRUE if entities should not be double encoded
	 * @return string
	 */
	public function htmlSpecialChars($string, $preserveEntities = FALSE) {
		return htmlspecialchars($string, NULL, NULL, !$preserveEntities);
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}
