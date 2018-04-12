<?php
namespace Neos\Flow\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * The Utility class for locale specific actions
 *
 * @Flow\Scope("singleton")
 */
class Utility
{
    /**
     * A pattern which matches HTTP Accept-Language Headers
     */
    const PATTERN_MATCH_ACCEPTLANGUAGE = '/([a-z]{1,8}(-[a-z]{1,8})?|\*)(;q=(1|0(\.[0-9]+)?))?/i';

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
    public static function parseAcceptLanguageHeader($acceptLanguageHeader)
    {
        $acceptLanguageHeader = str_replace(' ', '', $acceptLanguageHeader);
        $matchingLanguages = [];

        if (preg_match_all(self::PATTERN_MATCH_ACCEPTLANGUAGE, $acceptLanguageHeader, $matches, \PREG_PATTERN_ORDER) !== false) {
            foreach ($matches[1] as $localeIdentifier) {
                if ($localeIdentifier === '*') {
                    $matchingLanguages[] = $localeIdentifier;
                    continue;
                }

                if (strpos($localeIdentifier, '-') !== false) {
                    list($language, $region) = explode('-', $localeIdentifier);
                } else {
                    $language = $localeIdentifier;
                    $region = null;
                }

                if (strlen($language) >= 2 && strlen($language) <= 3) {
                    if ($region === null || strlen($region) >= 2 && strlen($region) <= 3) {
                        // Note: there are 3 chars in the region code only if they are all digits, but we don't check it above
                        $matchingLanguages[] = $localeIdentifier;
                    }
                }
            }

            if (count($matchingLanguages) > 0) {
                return $matchingLanguages;
            }
        }

        return false;
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
    public static function extractLocaleTagFromFilename($filename)
    {
        if (strpos($filename, '.') === false) {
            return false;
        }

        $filenameParts = explode('.', $filename);

        if (in_array($filenameParts[count($filenameParts) - 2], ['php', 'rss', 'xml'])) {
            return false;
        } elseif (count($filenameParts) === 2 && preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $filenameParts[0]) === 1) {
            return $filenameParts[0];
        } elseif (preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $filenameParts[count($filenameParts) - 2]) === 1) {
            return $filenameParts[count($filenameParts) - 2];
        } else {
            return false;
        }
    }

    /**
     * Extracts a locale tag (identifier) from the directory name given.
     *
     * Note: Locale tag will be extracted from the last directory path segment only.
     *
     * @param string $directory Directory path to extract locale identifier from
     * @return mixed The string with extracted locale identifier of FALSE on failure
     */
    public static function extractLocaleTagFromDirectory($directory)
    {
        $directoryParts = explode('/', rtrim($directory, '/'));
        $lastDirectoryPart = array_pop($directoryParts);

        if ($lastDirectoryPart !== null && preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $lastDirectoryPart) === 1) {
            return $lastDirectoryPart;
        }

        return false;
    }

    /**
     * Checks if $haystack string begins with $needle string.
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean TRUE if $haystack begins with $needle
     */
    public static function stringBeginsWith($haystack, $needle)
    {
        if (!empty($needle) && strncmp($haystack, $needle, strlen($needle)) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Checks if $haystack string ends with $needle string.
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean TRUE if $haystack ends with $needle
     */
    public static function stringEndsWith($haystack, $needle)
    {
        if (substr($haystack, - strlen($needle)) === $needle) {
            return true;
        }

        return false;
    }
}
