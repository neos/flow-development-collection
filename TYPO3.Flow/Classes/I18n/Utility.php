<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n;

/* *
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
 * The Utility class for locale specific actions
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Utility {

	/**
	 * A pattern which matches HTTP Accept-Language Headers
	 *
	 * @todo Zero-quality (means "any except this language") is not handled
	 */
	const PATTERN_MATCH_ACCEPTLANGUAGE = '/([a-z]{1,8}(-[a-z]{1,8})?|\*)(;q=(1|0\.[0-9]+))?/';

	/**
	 * Parses Accept-Language header and returns array of locale tags (like:
	 * en-GB, en), or FALSE if no tags were found.
	 *
	 * This method only returns tags that conforms ISO 639 for language codes
	 * and ISO 3166 for region codes. HTTP spec (RFC 2616) defines both of these
	 * parts as 1*8ALPHA, but this method ignores tags with longer (or shorter)
	 * codes.
	 *
	 * There can be an asterisk "*" in the returned array, which means that
	 * any language is acceptable.
	 *
	 * Warning: This method expects that locale tags are placed in descending
	 * order by quality in the $header string. I'm not sure if it's always true
	 * with the web browsers.
	 *
	 * @param string $header
	 * @return mixed The array of locale identifiers or FALSE
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	static public function parseAcceptLanguageHeader($header) {
			// Remove spaces as it simplifies regex pattern
		$header = str_replace(' ', '', $header);
		$matchingLanguages = array();

		if (preg_match_all(self::PATTERN_MATCH_ACCEPTLANGUAGE, $header, $matches, PREG_PATTERN_ORDER) !== FALSE) {
			foreach ($matches[1] as $tag) {
				if ($tag === '*') {
					$matchingLanguages[] = $tag;
					continue;
				}

				$tagElements = explode('-', $tag);
				if (strlen($tagElements[0]) >= 2 && strlen($tagElements[0]) <= 3) {
					if (count($tagElements) === 1 || strlen($tagElements[1]) >= 2 && strlen($tagElements[1]) <= 3) {
							// Note: there are 3 chars in the region code only if they are all digits, but we don't check it above
						$matchingLanguages[] = $tag;
					}
				}
			}

			if (count($matchingLanguages) > 0) {
				return $matchingLanguages;
			}
		}

		return FALSE;
	}

	/**
	 * Extracts a locale tag (identifier) from the filename given. Locale tag
	 * should be placed just before the extension of the file. For example,
	 * filename /foo/bar.png can be localized as /foo/bar.en_GB.png, and this
	 * method extracts en_GB from the name.
	 *
	 * Note: it's not guaranteed that this method will return valid locale tag.
	 *
	 * @param string $filename
	 * @return mixed The string with extracted locale of FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	static public function extractLocaleTagFromFilename($filename) {
		$filenameParts = explode('.', $filename);

		if (count($filenameParts) < 3) {
			return FALSE;
		}

		return $filenameParts[count($filenameParts) - 2];
	}

	/**
	 * Checks if $haystack string begins with $needle string.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool TRUE if $haystack begins with $needle
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	static public function stringBeginsWith($haystack, $needle) {
		if (strncmp($haystack, $needle, strlen($needle)) === 0) {
			return TRUE;
		}

		return FALSE;
	}
}

?>