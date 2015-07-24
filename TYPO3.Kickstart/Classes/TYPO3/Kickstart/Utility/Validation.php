<?php
namespace TYPO3\Kickstart\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Kickstart".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Validation utilities for the Kickstarter.
 *
 */
class Validation {

	/**
	 * Check the given keyword to be not one of the reserved words of PHP.
	 *
	 * @param string $keyword
	 * @return boolean
	 * @see http://www.php.net/manual/en/reserved.keywords.php
	 */
	static public function isReservedKeyword($keyword) {
		$reservedKeywords = array(
			'__halt_compiler',
			'abstract',
			'and',
			'array',
			'as',
			'break',
			'callable',
			'case',
			'catch',
			'class',
			'clone',
			'const',
			'continue',
			'declare',
			'default',
			'die',
			'do',
			'echo',
			'else',
			'elseif',
			'empty',
			'enddeclare',
			'endfor',
			'endforeach',
			'endif',
			'endswitch',
			'endwhile',
			'eval',
			'exit',
			'extends',
			'final',
			'for',
			'foreach',
			'function',
			'global',
			'goto',
			'if',
			'implements',
			'include',
			'include_once',
			'instanceof',
			'insteadof',
			'interface',
			'isset',
			'list',
			'namespace',
			'new',
			'or',
			'print',
			'private',
			'protected',
			'public',
			'require',
			'require_once',
			'return',
			'static',
			'switch',
			'throw',
			'trait',
			'try',
			'unset',
			'use',
			'var',
			'while',
			'xor'
		);

		return in_array(strtolower($keyword), $reservedKeywords);
	}
}