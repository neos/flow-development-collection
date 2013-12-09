<?php
namespace TYPO3\Flow\I18n;

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
 * The Utility class for locale specific actions
 *
 * @Flow\Scope("singleton")
 */
class Utility {

	/**
	 * A pattern which matches HTTP Accept-Language Headers
	 */
	const PATTERN_MATCH_ACCEPTLANGUAGE = '/([a-z]{1,8}(-[a-z]{1,8})?|\*)(;q=(1|0(\.[0-9]+)?))?/';

	/**
	 * Parses Accept-Language header and returns array of locale tags (like:
	 * en-GB, en), or FALSE if no tags were found.
	 *
	 * This method only returns tags that conforms ISO 639 for language codes
	 * and ISO 3166 for region codes. HTTP spec (RFC 2616) defines both of these
	 * parts as 1*8ALPHA, but this method ignores tags with longer (or shorter)
	 * codes than defined in ISO mentioned above.
	 *
	 * There can be an asterisk "*" in the returned array, which means that
	 * any language is acceptable.
	 *
	 * Warning: This method expects that locale tags are placed in descending
	 * order by quality in the $header string. I'm not sure if it's always true
	 * with the web browsers.
	 *
	 * @param string $acceptLanguageHeader
	 * @return mixed The array of locale identifiers or FALSE
	 */
	static public function parseAcceptLanguageHeader($acceptLanguageHeader) {
		$acceptLanguageHeader = str_replace(' ', '', $acceptLanguageHeader);
		$matchingLanguages = array();

		if (preg_match_all(self::PATTERN_MATCH_ACCEPTLANGUAGE, $acceptLanguageHeader, $matches, \PREG_PATTERN_ORDER) !== FALSE) {
			foreach ($matches[1] as $localeIdentifier) {
				if ($localeIdentifier === '*') {
					$matchingLanguages[] = $localeIdentifier;
					continue;
				}

				if (strpos($localeIdentifier, '-') !== FALSE) {
					list($language, $region) = explode('-', $localeIdentifier);
				} else {
					$language = $localeIdentifier;
					$region = NULL;
				}

				if (strlen($language) >= 2 && strlen($language) <= 3) {
					if ($region === NULL || strlen($region) >= 2 && strlen($region) <= 3) {
						// Note: there are 3 chars in the region code only if they are all digits, but we don't check it above
						$matchingLanguages[] = $localeIdentifier;
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
	 * Extracts a locale tag (identifier) from the filename given.
	 *
	 * Locale tag should be placed just before the extension of the file. For
	 * example, filename bar.png can be localized as bar.en_GB.png,
	 * and this method extracts en_GB from the name.
	 *
	 * Note: this ignores matches on rss, xml and php and validates the identifier.
	 *
	 * @param string $filename Filename to extract locale identifier from
	 * @return mixed The string with extracted locale identifier of FALSE on failure
	 */
	static public function extractLocaleTagFromFilename($filename) {
		if (strpos($filename, '.') === FALSE) {
			return FALSE;
		}

		$filenameParts = explode('.', $filename);

		if (in_array($filenameParts[count($filenameParts) - 2], array('php', 'rss', 'xml'))) {
			return FALSE;
		} elseif (count($filenameParts) === 2 && preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $filenameParts[0]) === 1) {
			return $filenameParts[0];
		} elseif (preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $filenameParts[count($filenameParts) - 2]) === 1) {
			return $filenameParts[count($filenameParts) - 2];
		} else {
			return FALSE;
		}
	}

	/**
	 * Checks if $haystack string begins with $needle string.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean TRUE if $haystack begins with $needle
	 */
	static public function stringBeginsWith($haystack, $needle) {
		if (!empty($needle) && strncmp($haystack, $needle, strlen($needle)) === 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Checks if $haystack string ends with $needle string.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean TRUE if $haystack ends with $needle
	 */
	static public function stringEndsWith($haystack, $needle) {
		if (substr($haystack, - strlen($needle)) === $needle) {
			return TRUE;
		}

		return FALSE;
	}
}
