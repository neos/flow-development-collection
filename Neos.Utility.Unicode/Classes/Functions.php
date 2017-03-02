<?php
namespace Neos\Utility\Unicode;

/*
 * This file is part of the Neos.Utility.Unicode package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A class with UTF-8 string functions, some inspired by what might be in some
 * future PHP version...
 *
 * @api
 */
abstract class Functions
{

    /**
     * Converts the first character of each word to uppercase and all remaining characters
     * to lowercase.
     *
     * @param  string $string The string to convert
     * @return string The converted string
     * @api
     */
    public static function strtotitle($string)
    {
        $result = '';
        $splitIntoLowerCaseWords = preg_split("/([\n\r\t ])/", self::strtolower($string), -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($splitIntoLowerCaseWords as $delimiterOrValue) {
            $result .= self::strtoupper(self::substr($delimiterOrValue, 0, 1)) . self::substr($delimiterOrValue, 1);
        }
        return $result;
    }

    /**
     * Unicode variant of substr()
     *
     * @param string $string The string to crop
     * @param integer $start Position of the left boundary
     * @param integer $length (optional) Length of the returned string
     * @return string The processed string
     * @api
     */
    public static function substr($string, $start, $length = null)
    {
        if ($length === 0) {
            return '';
        }

        // Cannot omit $length, when specifying charset
        if ($length === null) {
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
     * Unicode variant of strtoupper()
     *
     * @param  string $string The string to uppercase
     * @return string The processed string
     * @api
     */
    public static function strtoupper($string)
    {
        return str_replace('ß', 'SS', mb_strtoupper($string, 'UTF-8'));
    }

    /**
     * Unicode variant of strtolower()
     *
     * @param  string $string The string to lowercase
     * @return string The processed string
     * @api
     */
    public static function strtolower($string)
    {
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * Uniocde variant of strlen() - assumes that the string is a Unicode string, not binary
     *
     * @param  string $string The string to count the characters of
     * @return integer The number of characters
     * @api
     */
    public static function strlen($string)
    {
        return mb_strlen($string, 'UTF-8');
    }

    /**
     * Unicode variant of ucfirst() - assumes that the string is a Unicode string, not binary
     *
     * @param  string $string The string whose first letter should be uppercased
     * @return string The same string, first character uppercased
     * @api
     */
    public static function ucfirst($string)
    {
        return self::strtoupper(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

    /**
     * Unicode variant of lcfirst() - assumes that the string is a Unicode string, not binary
     *
     * @param  string $string The string whose first letter should be lowercased
     * @return string The same string, first character lowercased
     * @api
     */
    public static function lcfirst($string)
    {
        return self::strtolower(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

    /**
     * Unicode variant of strpos() - assumes that the string is a Unicode string, not binary
     *
     * @param string $haystack UTF-8 string to search in
     * @param string $needle UTF-8 string to search for
     * @param integer $offset Positition to start the search
     * @return integer The character position
     * @api
     */
    public static function strpos($haystack, $needle, $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset, 'UTF-8');
    }

    /**
     * Unicode variant of pathinfo()
     * pathinfo() function is not unicode-friendly
     * if setlocale is not set. It's sufficient to set it
     * to any UTF-8 locale to correctly handle unicode strings.
     * This wrapper function temporarily sets locale to 'en_US.UTF-8'
     * and then restores original locale.
     * It's not necessary to use this function in cases,
     * where only file extension is determined, as it's
     * hard to imagine a unicode file extension.
     * @see http://www.php.net/manual/en/function.pathinfo.php
     *
     * @param string $path
     * @param integer $options Optional, one of PATHINFO_DIRNAME, PATHINFO_BASENAME, PATHINFO_EXTENSION or PATHINFO_FILENAME.
     * @return string|array
     * @api
     */
    public static function pathinfo($path, $options = null)
    {
        $currentLocale = setlocale(LC_CTYPE, 0);
        // Before we have a setting for setlocale, his should suffice for pathinfo
        // to work correctly on Unicode strings
        setlocale(LC_CTYPE, 'en_US.UTF-8');
        $pathinfo = $options == null ? pathinfo($path) : pathinfo($path, $options);
        setlocale(LC_CTYPE, $currentLocale);
        return $pathinfo;
    }

    /**
     * Parse a URL and return its components, UTF-8 safe
     *
     * @param string $url The URL to parse. Invalid characters are replaced by _.
     * @param integer $component Specify one of PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH, PHP_URL_QUERY or PHP_URL_FRAGMENT to retrieve just a specific URL component as a string (except when PHP_URL_PORT is given, in which case the return value will be an integer).
     * @return mixed
     */
    public static function parse_url($url, $component = -1)
    {
        $encodedUrl = preg_replace_callback('%[^:@/?#&=\.]+%usD', function ($matches) {
            return urlencode($matches[0]);
        }, $url);
        $components = parse_url($encodedUrl);

        if ($components === false) {
            return false;
        }

        foreach ($components as &$currentComponent) {
            $currentComponent = urldecode($currentComponent);
        }

        if (array_key_exists('port', $components)) {
            $components['port'] = (integer)$components['port'];
        }

        switch ($component) {
            case -1:
                return $components;
            case PHP_URL_SCHEME:
                return $components['scheme'];
            case PHP_URL_HOST:
                return $components['host'];
            case PHP_URL_PORT:
                return $components['port'];
            case PHP_URL_USER:
                return $components['user'];
            case PHP_URL_PASS:
                return $components['pass'];
            case PHP_URL_PATH:
                return $components['path'];
            case PHP_URL_QUERY:
                return $components['query'];
            case PHP_URL_FRAGMENT:
                return $components['fragment'];
            default:
                throw new \InvalidArgumentException('Invalid component requested for URL parsing.', 1406280743);
        }
    }
}
