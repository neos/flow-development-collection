<?php
namespace Neos\Flow\Http;

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
 * Represents a URI Template as per http://tools.ietf.org/html/rfc6570
 *
 * @api
 * @Flow\Proxy(false)
 */
class UriTemplate
{
    /**
     * @var array
     */
    protected static $variables;

    /**
     * @var array
     */
    protected static $operators = [
        '+' => true, '#' => true, '.' => true, '/' => true, ';' => true, '?' => true, '&' => true
    ];

    /**
     * @var array
     */
    protected static $delimiters = [':', '/', '?', '#', '[', ']', '@', '!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '='];

    /**
     * @var array
     */
    protected static $encodedDelimiters = ['%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D'];

    /**
     * Expand the template string using the supplied variables
     *
     * @param string $template URI template to expand
     * @param array $variables variables to use with the expansion
     * @return string
     */
    public static function expand($template, array $variables)
    {
        if (strpos($template, '{') === false) {
            return $template;
        }

        self::$variables = $variables;

        return preg_replace_callback('/\{([^\}]+)\}/', [UriTemplate::class, 'expandMatch'], $template);
    }

    /**
     * Process an expansion
     *
     * @param array $matches matches found in preg_replace_callback
     * @return string replacement string
     */
    protected static function expandMatch(array $matches)
    {
        $parsed = self::parseExpression($matches[1]);
        $replacements = [];

        $prefix = $parsed['operator'];
        $separator = $parsed['operator'];
        $queryStringShouldBeUsed = false;
        switch ($parsed['operator']) {
            case '?':
                $separator = '&';
                $queryStringShouldBeUsed = true;
                break;
            case '#':
                $separator = ',';
                break;
            case '&':
            case ';':
                $queryStringShouldBeUsed = true;
                break;
            case '+':
            case '':
                $separator = ',';
                $prefix = '';
                break;
        }

        foreach ($parsed['values'] as $value) {
            if (!array_key_exists($value['value'], self::$variables) || self::$variables[$value['value']] === null) {
                continue;
            }

            $variable = self::$variables[$value['value']];
            $useQueryString = $queryStringShouldBeUsed;

            if (is_array($variable)) {
                $expanded = self::encodeArrayVariable($variable, $value, $parsed['operator'], $separator, $useQueryString);
            } else {
                if ($value['modifier'] === ':') {
                    $variable = substr($variable, 0, $value['position']);
                }
                $expanded = rawurlencode($variable);
                if ($parsed['operator'] === '+' || $parsed['operator'] === '#') {
                    $expanded = self::decodeReservedDelimiters($expanded);
                }
            }

            if ($useQueryString) {
                if ($expanded === '' && $separator !== '&') {
                    $expanded = $value['value'];
                } else {
                    $expanded = $value['value'] . '=' . $expanded;
                }
            }

            $replacements[] = $expanded;
        }

        $result = implode($separator, $replacements);
        if ($result !== '' && $prefix !== '') {
            return $prefix . $result;
        }

        return $result;
    }

    /**
     * Parse an expression into parts
     *
     * @param string $expression Expression to parse
     * @return array associative array of parts
     */
    protected static function parseExpression($expression)
    {
        if (isset(self::$operators[$expression[0]])) {
            $operator = $expression[0];
            $expression = substr($expression, 1);
        } else {
            $operator = '';
        }

        $explodedExpression = explode(',', $expression);
        foreach ($explodedExpression as &$expressionPart) {
            $configuration = [];
            $expressionPart = trim($expressionPart);
            $colonPosition = strpos($expressionPart, ':');

            if ($colonPosition) {
                $configuration['value'] = substr($expressionPart, 0, $colonPosition);
                $configuration['modifier'] = ':';
                $configuration['position'] = (int)substr($expressionPart, $colonPosition + 1);
            } elseif (substr($expressionPart, -1) === '*') {
                $configuration['modifier'] = '*';
                $configuration['value'] = substr($expressionPart, 0, -1);
            } else {
                $configuration['value'] = (string)$expressionPart;
                $configuration['modifier'] = '';
            }

            $expressionPart = $configuration;
        }

        return [
            'operator' => $operator,
            'values' => $explodedExpression
        ];
    }

    /**
     * Encode arrays for use in the expanded URI string
     *
     * @param array $variable
     * @param array $value
     * @param string $operator
     * @param string $separator
     * @param $useQueryString
     * @return string
     */
    protected static function encodeArrayVariable(array $variable, array $value, $operator, $separator, &$useQueryString)
    {
        $isAssociativeArray = self::isAssociative($variable);
        $keyValuePairs = [];

        foreach ($variable as $key => $var) {
            if ($isAssociativeArray) {
                $key = rawurlencode($key);
                $isNestedArray = is_array($var);
            } else {
                $isNestedArray = false;
            }

            if (!$isNestedArray) {
                $var = rawurlencode($var);
                if ($operator === '+' || $operator === '#') {
                    $var = self::decodeReservedDelimiters($var);
                }
            }

            if ($value['modifier'] === '*') {
                if ($isAssociativeArray) {
                    if ($isNestedArray) {
                        // allow for deeply nested structures
                        $var = strtr(http_build_query([$key => $var]), ['+' => '%20', '%7e' => '~']);
                    } else {
                        $var = $key . '=' . $var;
                    }
                } elseif ($key > 0 && $useQueryString) {
                    $var = $value['value'] . '=' . $var;
                }
            }

            $keyValuePairs[$key] = $var;
        }

        $expanded = '';
        if (empty($variable)) {
            $useQueryString = false;
        } elseif ($value['modifier'] === '*') {
            $expanded = implode($separator, $keyValuePairs);
            if ($isAssociativeArray) {
                // Don't prepend the value name when using the explode modifier with an associative array
                $useQueryString = false;
            }
        } else {
            if ($isAssociativeArray) {
                // The result must be a comma separated list of keys followed by their respective values
                // if the explode modifier is not set on an associative array
                foreach ($keyValuePairs as $k => &$v) {
                    $v = $k . ',' . $v;
                }
            }
            $expanded = implode(',', $keyValuePairs);
        }

        return $expanded;
    }

    /**
     * Determines if an array is associative, i.e. contains at least one key that is a string.
     *
     * @param array $array
     * @return boolean
     */
    protected static function isAssociative(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * Decodes percent encoding on delimiters (used with + and # modifiers)
     *
     * @param string $string
     * @return string
     */
    protected static function decodeReservedDelimiters($string)
    {
        return str_replace(self::$encodedDelimiters, self::$delimiters, $string);
    }
}
