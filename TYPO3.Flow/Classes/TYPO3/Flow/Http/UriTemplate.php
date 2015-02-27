<?php
namespace TYPO3\Flow\Http;

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
 * Represents a URI Template as per http://tools.ietf.org/html/rfc6570
 *
 * @api
 * @Flow\Proxy(false)
 */
class UriTemplate {

	/**
	 * @var array
	 */
	static protected $variables;

	/**
	 * @var array
	 */
	static protected $operators = array(
		'+' => TRUE, '#' => TRUE, '.' => TRUE, '/' => TRUE, ';' => TRUE, '?' => TRUE, '&' => TRUE
	);

	/**
	 * @var array
	 */
	static protected $delimiters = array(':', '/', '?', '#', '[', ']', '@', '!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '=');

	/**
	 * @var array
	 */
	static protected $encodedDelimiters = array('%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D');

	/**
	 * Expand the template string using the supplied variables
	 *
	 * @param string $template URI template to expand
	 * @param array $variables variables to use with the expansion
	 * @return string
	 */
	static public function expand($template, array $variables) {
		if (strpos($template, '{') === FALSE) {
			return $template;
		}

		self::$variables = $variables;

		return preg_replace_callback('/\{([^\}]+)\}/', array('TYPO3\Flow\Http\UriTemplate', 'expandMatch'), $template);
	}

	/**
	 * Process an expansion
	 *
	 * @param array $matches matches found in preg_replace_callback
	 * @return string replacement string
	 */
	static protected function expandMatch(array $matches) {
		$parsed = self::parseExpression($matches[1]);
		$replacements = array();

		$prefix = $parsed['operator'];
		$separator = $parsed['operator'];
		$queryStringShouldBeUsed = FALSE;
		switch ($parsed['operator']) {
			case '?':
				$separator = '&';
				$queryStringShouldBeUsed = TRUE;
				break;
			case '#':
				$separator = ',';
				break;
			case '&':
			case ';':
				$queryStringShouldBeUsed = TRUE;
				break;
			case '+':
			case '':
				$separator = ',';
				$prefix = '';
				break;
		}

		foreach ($parsed['values'] as $value) {
			if (!array_key_exists($value['value'], self::$variables) || self::$variables[$value['value']] === NULL) {
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
	static protected function parseExpression($expression) {
		if (isset(self::$operators[$expression[0]])) {
			$operator = $expression[0];
			$expression = substr($expression, 1);
		} else {
			$operator = '';
		}

		$explodedExpression = explode(',', $expression);
		foreach ($explodedExpression as &$expressionPart) {
			$configuration = array();
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

		return array(
			'operator' => $operator,
			'values' => $explodedExpression
		);
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
	static protected function encodeArrayVariable(array $variable, array $value, $operator, $separator, &$useQueryString) {
		$isAssociativeArray = self::isAssociative($variable);
		$keyValuePairs = array();

		foreach ($variable as $key => $var) {
			if ($isAssociativeArray) {
				$key = rawurlencode($key);
				$isNestedArray = is_array($var);
			} else {
				$isNestedArray = FALSE;
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
						$var = strtr(http_build_query(array($key => $var)), array('+' => '%20', '%7e' => '~'));
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
			$useQueryString = FALSE;
		} elseif ($value['modifier'] === '*') {
			$expanded = implode($separator, $keyValuePairs);
			if ($isAssociativeArray) {
				// Don't prepend the value name when using the explode modifier with an associative array
				$useQueryString = FALSE;
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
	static protected function isAssociative(array $array) {
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}

	/**
	 * Decodes percent encoding on delimiters (used with + and # modifiers)
	 *
	 * @param string $string
	 * @return string
	 */
	static protected function decodeReservedDelimiters($string) {
		return str_replace(self::$encodedDelimiters, self::$delimiters, $string);
	}
}
