<?php
namespace Neos\Eel\Helper;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\EvaluationException;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\Utility\Unicode\TextIterator;

/**
 * String helpers for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class StringHelper implements ProtectedContextAwareInterface
{
    /**
     * Return the characters in a string from start up to the given length
     *
     * This implementation follows the JavaScript specification for "substr".
     *
     * Examples::
     *
     *     String.substr('Hello, World!', 7, 5) == 'World'
     *     String.substr('Hello, World!', 7) == 'World!'
     *     String.substr('Hello, World!', -6) == 'World!'
     *
     * @param string $string A string
     * @param integer $start Start offset
     * @param integer $length Maximum length of the substring that is returned
     * @return string The substring
     */
    public function substr($string, $start, $length = null)
    {
        $string = (string)$string;

        if ($length === null) {
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
     * Examples::
     *
     *     String.substring('Hello, World!', 7, 12) == 'World'
     *     String.substring('Hello, World!', 7) == 'World!'
     *
     * @param string $string
     * @param integer $start Start index
     * @param integer $end End index
     * @return string The substring
     */
    public function substring($string, $start, $end = null)
    {
        $string = (string)$string;

        if ($end === null) {
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
     * Get the character at a specific position
     *
     * Example::
     *
     *     String.charAt("abcdefg", 5) == "f"
     *
     * @param string $string The input string
     * @param integer $index The index to get
     * @return string The character at the given index
     */
    public function charAt($string, $index)
    {
        if ($index < 0) {
            return '';
        }
        return mb_substr((string)$string, $index, 1, 'UTF-8');
    }

    /**
     * Test if a string ends with the given search string
     *
     * Example::
     *
     *     String.endsWith('Hello, World!', 'World!') == true
     *
     * @param string $string The string
     * @param string $search A string to search
     * @param integer $position Optional position for limiting the string
     * @return boolean true if the string ends with the given search
     */
    public function endsWith($string, $search, $position = null)
    {
        $string = (string)$string;

        $position = $position !== null ? $position : mb_strlen($string, 'UTF-8');
        $position = $position - mb_strlen($search, 'UTF-8');
        return mb_strrpos($string, $search, null, 'UTF-8') === $position;
    }

    /**
     * Generate a single-byte string from a number
     *
     * Example::
     *
     *     String.chr(65) == "A"
     *
     * This is a wrapper for the chr() PHP function.
     * @see ord()
     *
     * @param int $value An integer between 0 and 255
     * @return string A single-character string containing the specified byte
     */
    public function chr($value)
    {
        return chr((int)$value);
    }

    /**
     * Convert the first byte of a string to a value between 0 and 255
     *
     * Example::
     *
     *     String.ord('A') == 65
     *
     * This is a wrapper for the ord() PHP function.
     * @see chr()
     *
     * @param string $string A character
     * @return int An integer between 0 and 255
     */
    public function ord($string)
    {
        return ord((string)$string);
    }

    /**
     * Find the first position of a substring in the given string
     *
     * Example::
     *
     *     String.indexOf("Blue Whale", "Blue") == 0
     *
     * @param string $string The input string
     * @param string $search The substring to search for
     * @param integer $fromIndex The index where the search should start, defaults to the beginning
     * @return integer The index of the substring (>= 0) or -1 if the substring was not found
     */
    public function indexOf($string, $search, $fromIndex = null)
    {
        $string = (string)$string;

        $fromIndex = max(0, $fromIndex);
        if ($search === '') {
            return min(mb_strlen($string, 'UTF-8'), $fromIndex);
        }
        $index = mb_strpos($string, $search, $fromIndex, 'UTF-8');
        if ($index === false) {
            return -1;
        }
        return (integer)$index;
    }

    /**
     * Find the last position of a substring in the given string
     *
     * Example::
     *
     *     String.lastIndexOf("Developers Developers Developers!", "Developers") == 22
     *
     * @param string $string The input string
     * @param string $search The substring to search for
     * @param integer $toIndex The position where the backwards search should start, defaults to the end
     * @return integer The last index of the substring (>=0) or -1 if the substring was not found
     */
    public function lastIndexOf($string, $search, $toIndex = null)
    {
        $string = (string)$string;

        $length = mb_strlen($string, 'UTF-8');
        if ($toIndex === null) {
            $toIndex = $length;
        }
        $toIndex = max(0, $toIndex);
        if ($search === '') {
            return min($length, $toIndex);
        }
        $string = mb_substr($string, 0, $toIndex, 'UTF-8');
        $index = mb_strrpos($string, $search, 0, 'UTF-8');
        if ($index === false) {
            return -1;
        }
        return (integer)$index;
    }

    /**
     * Match a string with a regular expression (PREG style)
     *
     * Example::
     *
     *     String.pregMatch("For more information, see Chapter 3.4.5.1", "/(chapter \d+(\.\d)*)/i")
     *       == ['Chapter 3.4.5.1', 'Chapter 3.4.5.1', '.1']
     *
     * @param string $string The input string
     * @param string $pattern A PREG pattern
     * @return array The matches as array or NULL if not matched
     * @throws EvaluationException
     */
    public function pregMatch($string, $pattern)
    {
        $number = preg_match($pattern, (string)$string, $matches);
        if ($number === false) {
            throw new EvaluationException('Error evaluating regular expression ' . $pattern . ': ' . preg_last_error(), 1372793595);
        }
        if ($number === 0) {
            return null;
        }
        return $matches;
    }

    /**
     * Perform a global regular expression match (PREG style)
     *
     * Example::
     *
     *     String.pregMatchAll("<hr id="icon-one" /><hr id="icon-two" />", '/id="icon-(.+?)"/')
     *       == [['id="icon-one"', 'id="icon-two"'],['one','two']]
     *
     * @param string $string The input string
     * @param string $pattern A PREG pattern
     * @return array The matches as array or NULL if not matched
     * @throws EvaluationException
     */
    public function pregMatchAll($string, $pattern)
    {
        $number = preg_match_all($pattern, (string)$string, $matches);
        if ($number === false) {
            throw new EvaluationException('Error evaluating regular expression ' . $pattern . ': ' . preg_last_error(), 1372793595);
        }
        if ($number === 0) {
            return null;
        }
        return $matches;
    }

    /**
     * Replace occurrences of a search string inside the string using regular expression matching (PREG style)
     *
     * Examples::
     *
     *     String.pregReplace("Some.String with sp:cial characters", "/[[:^alnum:]]/", "-") == "Some-String-with-sp-cial-characters"
     *     String.pregReplace("Some.String with sp:cial characters", "/[[:^alnum:]]/", "-", 1) == "Some-String with sp:cial characters"
     *     String.pregReplace("2016-08-31", "/([0-9]+)-([0-9]+)-([0-9]+)/", "$3.$2.$1") == "31.08.2016"
     *
     * @param string $string The input string
     * @param string $pattern A PREG pattern
     * @param string $replace A replacement string, can contain references to capture groups with "\\n" or "$n"
     * @param integer $limit The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
     * @return string The string with all occurrences replaced
     */
    public function pregReplace($string, $pattern, $replace, $limit = -1)
    {
        if ($limit === null) {
            $limit = -1;
        }
        return preg_replace($pattern, $replace, (string)$string, $limit);
    }

    /**
     * Split a string by a separator using regular expression matching (PREG style)
     *
     * Examples::
     *
     *     String.pregSplit("foo bar   baz", "/\s+/") == ['foo', 'bar', 'baz']
     *     String.pregSplit("first second third", "/\s+/", 2) == ['first', 'second third']
     *
     * @param string $string The input string
     * @param string $pattern A PREG pattern
     * @param integer $limit The maximum amount of items to return, in contrast to split() this will return all remaining characters in the last item (see example)
     * @return array An array of the splitted parts, excluding the matched pattern
     */
    public function pregSplit($string, $pattern, $limit = null)
    {
        return preg_split($pattern, (string)$string, $limit);
    }

    /**
     * Replace occurrences of a search string inside the string
     *
     * Example::
     *
     *     String.replace("canal", "ana", "oo") == "cool"
     *
     * Note: this method does not perform regular expression matching, @see pregReplace().
     *
     * @param string $string The input string
     * @param string $search A search string
     * @param string $replace A replacement string
     * @return string The string with all occurrences replaced
     */
    public function replace($string, $search, $replace)
    {
        return str_replace($search, $replace, (string)$string);
    }

    /**
     * Split a string by a separator
     *
     * Example::
     *
     *     String.split("My hovercraft is full of eels", " ") == ['My', 'hovercraft', 'is', 'full', 'of', 'eels']
     *     String.split("Foo", "", 2) == ['F', 'o']
     *
     * Node: This implementation follows JavaScript semantics without support of regular expressions.
     *
     * @param string $string The string to split
     * @param string $separator The separator where the string should be splitted
     * @param integer $limit The maximum amount of items to split (exceeding items will be discarded)
     * @return array An array of the splitted parts, excluding the separators
     */
    public function split($string, $separator = null, $limit = null)
    {
        $string = (string)$string;

        if ($separator === null) {
            return [$string];
        }
        if ($separator === '') {
            $result = str_split($string);
            if ($limit !== null) {
                $result = array_slice($result, 0, $limit);
            }
            return $result;
        }
        if ($limit === null) {
            $result = explode($separator, $string);
        } else {
            $result = explode($separator, $string, $limit);
        }
        return $result;
    }

    /**
     * Test if a string starts with the given search string
     *
     * Examples::
     *
     *     String.startsWith('Hello world!', 'Hello') == true
     *     String.startsWith('My hovercraft is full of...', 'Hello') == false
     *     String.startsWith('My hovercraft is full of...', 'hovercraft', 3) == true
     *
     * @param string $string The input string
     * @param string $search The string to search for
     * @param integer $position The position to test (defaults to the beginning of the string)
     * @return boolean
     */
    public function startsWith($string, $search, $position = null)
    {
        $position = $position !== null ? $position : 0;
        return mb_strpos((string)$string, $search, null, 'UTF-8') === $position;
    }

    /**
     * Lowercase a string
     *
     * @param string $string The input string
     * @return string The string in lowercase
     */
    public function toLowerCase($string)
    {
        return mb_strtolower((string)$string, 'UTF-8');
    }

    /**
     * Uppercase a string
     *
     * @param string $string The input string
     * @return string The string in uppercase
     */
    public function toUpperCase($string)
    {
        return mb_strtoupper((string)$string, 'UTF-8');
    }

    /**
     * Uppercase the first letter of a string
     *
     * Example::
     *
     *     String.firstLetterToUpperCase('hello world') == 'Hello world'
     *
     * @param string $string The input string
     * @return string The string with the first letter in uppercase
     */
    public function firstLetterToUpperCase($string)
    {
        return UnicodeFunctions::ucfirst((string)$string);
    }

    /**
     * Lowercase the first letter of a string
     *
     * Example::
     *
     *     String.firstLetterToLowerCase('CamelCase') == 'camelCase'
     *
     * @param string $string The input string
     * @return string The string with the first letter in lowercase
     */
    public function firstLetterToLowerCase($string)
    {
        return UnicodeFunctions::lcfirst((string)$string);
    }

    /**
     * Strip all HTML tags from the given string
     *
     * Example::
     *
     *     String.stripTags('<a href="#">Some link</a>') == 'Some link'
     *
     * This is a wrapper for the strip_tags() PHP function.
     *
     * @param string $string The string to strip
     * @param string $allowableTags Specify tags which should not be stripped
     * @return string The string with tags stripped
     */
    public function stripTags($string, $allowableTags = null)
    {
        return strip_tags((string)$string, $allowableTags);
    }

    /**
     * Insert HTML line breaks before all newlines in a string
     *
     * Example::
     *
     *     String.nl2br(someStingWithLinebreaks) == 'line1<br />line2'
     *
     * This is a wrapper for the nl2br() PHP function.
     *
     * @param string $string The input string
     * @return string The string with new lines replaced
     */
    public function nl2br($string)
    {
        return nl2br((string)$string);
    }

    /**
     * Test if the given string is blank (empty or consists of whitespace only)
     *
     * Examples::
     *
     *     String.isBlank('') == true
     *     String.isBlank('  ') == true
     *
     * @param string $string The string to test
     * @return boolean ``true`` if the given string is blank
     */
    public function isBlank($string)
    {
        return trim((string)$string) === '';
    }

    /**
     * Trim whitespace at the beginning and end of a string
     *
     * @param string $string The string to trim
     * @param string $charlist List of characters that should be trimmed, defaults to whitespace
     * @return string The trimmed string
     */
    public function trim($string, $charlist = null)
    {
        $string = (string)$string;

        if ($charlist === null) {
            return trim($string);
        } else {
            return trim($string, $charlist);
        }
    }

    /**
     * Convert the given value to a string
     *
     * @param mixed $value The value to convert (must be convertible to string)
     * @return string The string value
     */
    public function toString($value)
    {
        return (string)$value;
    }

    /**
     * Convert a string to integer
     *
     * @param string $string The string to convert
     * @return integer The converted string
     */
    public function toInteger($string)
    {
        return (integer)$string;
    }

    /**
     * Convert a string to float
     *
     * @param string $string The string to convert
     * @return float The float value of the string
     */
    public function toFloat($string)
    {
        return (float)$string;
    }

    /**
     * Convert a string to boolean
     *
     * A value is ``true``, if it is either the string ``"true"`` or ``"true"`` or the number ``1``.
     *
     * @param string $string The string to convert
     * @return boolean The boolean value of the string (``true`` or ``false``)
     */
    public function toBoolean($string)
    {
        return strtolower((string)$string) === 'true' || (integer)$string === 1;
    }

    /**
     * Encode the string for URLs according to RFC 3986
     *
     * @param string $string The string to encode
     * @return string The encoded string
     */
    public function rawUrlEncode($string)
    {
        return rawurlencode((string)$string);
    }

    /**
     * Decode the string from URLs according to RFC 3986
     *
     * @param string $string The string to decode
     * @return string The decoded string
     */
    public function rawUrlDecode($string)
    {
        return rawurldecode((string)$string);
    }

    /**
     * Convert special characters to HTML entities
     *
     * @param string $string The string to convert
     * @param boolean $preserveEntities ``true`` if entities should not be double encoded
     * @return string The converted string
     */
    public function htmlSpecialChars($string, $preserveEntities = false)
    {
        return htmlspecialchars((string)$string, ENT_NOQUOTES | ENT_HTML401, ini_get("default_charset"), !$preserveEntities);
    }

    /**
     * Crop a string to ``maximumCharacters`` length, optionally appending ``suffix`` if cropping was necessary.
     *
     * @param string $string The input string
     * @param integer $maximumCharacters Number of characters where cropping should happen
     * @param string $suffix Suffix to be appended if cropping was necessary
     * @return string The cropped string
     */
    public function crop($string, $maximumCharacters, $suffix = '')
    {
        $string = (string)$string;

        if (UnicodeFunctions::strlen($string) > $maximumCharacters) {
            $string = UnicodeFunctions::substr($string, 0, $maximumCharacters);
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Crop a string to ``maximumCharacters`` length, taking words into account,
     * optionally appending ``suffix`` if cropping was necessary.
     *
     * @param string $string The input string
     * @param integer $maximumCharacters Number of characters where cropping should happen
     * @param string $suffix Suffix to be appended if cropping was necessary
     * @return string The cropped string
     */
    public function cropAtWord($string, $maximumCharacters, $suffix = '')
    {
        $string = (string)$string;

        if (UnicodeFunctions::strlen($string) > $maximumCharacters) {
            $iterator = new TextIterator($string, TextIterator::WORD);
            $string = UnicodeFunctions::substr($string, 0, $iterator->preceding($maximumCharacters));
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Crop a string to ``maximumCharacters`` length, taking sentences into account,
     * optionally appending ``suffix`` if cropping was necessary.
     *
     * @param string $string The input string
     * @param integer $maximumCharacters Number of characters where cropping should happen
     * @param string $suffix Suffix to be appended if cropping was necessary
     * @return string The cropped string
     */
    public function cropAtSentence($string, $maximumCharacters, $suffix = '')
    {
        $string = (string)$string;

        if (UnicodeFunctions::strlen($string) > $maximumCharacters) {
            $iterator = new TextIterator($string, TextIterator::SENTENCE);
            $string = UnicodeFunctions::substr($string, 0, $iterator->preceding($maximumCharacters));
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Calculate the MD5 checksum of the given string
     *
     * Example::
     *
     *     String.md5("joh316") == "bacb98acf97e0b6112b1d1b650b84971"
     *
     * @param string $string The string to hash
     * @return string The MD5 hash of ``string``
     */
    public function md5($string)
    {
        return md5((string)$string);
    }

    /**
     * Calculate the SHA1 checksum of the given string
     *
     * Example::
     *
     *     String.sha1("joh316") == "063b3d108bed9f88fa618c6046de0dccadcf3158"
     *
     * @param string $string The string to hash
     * @return string The SHA1 hash of ``string``
     */
    public function sha1($string)
    {
        return sha1((string)$string);
    }

    /**
     * Get the length of a string
     *
     * @param string $string The input string
     * @return integer Length of the string
     */
    public function length($string)
    {
        return UnicodeFunctions::strlen((string)$string);
    }

    /**
     * Return the count of words for a given string. Remove marks & digits and
     * flatten all kind of whitespaces (tabs, new lines and multiple spaces)
     * For example this helper can be utilized to calculate the reading time of an article.
     *
     * @param string $unicodeString The input string
     * @return integer Number of words
     */
    public function wordCount($unicodeString)
    {
        $unicodeString = (string)$unicodeString;

        $unicodeString = preg_replace('/[[:punct:][:digit:]]/', '', $unicodeString);

        return count(preg_split('/[[:space:]]+/', $unicodeString, 0, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Implementation of the PHP base64_encode function
     * @see https://php.net/manual/en/function.base64-encode.php
     *
     * @param string $string The data to encode.
     * @return string The encoded data
     */
    public function base64encode($string)
    {
        return base64_encode((string)$string);
    }

    /**
     * Implementation of the PHP base64_decode function
     * @see https://php.net/manual/en/function.base64-decode.php
     *
     * @param string $string The encoded data.
     * @param bool $strict If TRUE this function will return FALSE if the input contains character from outside the base64 alphabet.
     * @return string|bool The decoded data or FALSE on failure. The returned data may be binary.
     */
    public function base64decode($string, bool $strict = false)
    {
        return base64_decode((string)$string, $strict);
    }

    /**
     * Implementation of the PHP vsprintf function
     * @see https://php.net/manual/en/function.vsprintf.php
     *
     * @param string $format A formatting string containing directives
     * @param array $args An array of values to be inserted according to the formatting string $format
     * @return string A string produced according to the formatting string $format
     */
    public function format($format, array $args)
    {
        return vsprintf($format, $args);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
