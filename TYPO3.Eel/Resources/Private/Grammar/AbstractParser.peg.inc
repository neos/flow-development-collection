<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once __DIR__ . '/../../../Resources/Private/PHP/php-peg/Parser.php';

/*!* !insert_autogen_warning */

/**
 * This Abstract Parser class contains definitions for absolutely basic types,
 * like quoted strings or identifiers
 */
abstract class AbstractParser extends \PhpPeg\Parser {
/*!* WhiteSpace

S: / \s* /

*/

/*!* BasicTypes

_IntegerNumber: / -? [0-9]+ /
_Decimals: / \.[0-9]+ /
NumberLiteral: int:_IntegerNumber dec:_Decimals?

DoubleQuotedStringLiteral: '"' / (\\"|[^"])* / '"'
SingleQuotedStringLiteral: "\'" / (\\'|[^'])* / "\'"
StringLiteral: SingleQuotedStringLiteral | DoubleQuotedStringLiteral

BooleanLiteral: 'true' | 'TRUE' | 'false' | 'FALSE'

Identifier: / [a-zA-Z_] [a-zA-Z0-9_\-]* /
*/

    public function StringLiteral_SingleQuotedStringLiteral(&$result, $sub)
    {
        $result['val'] = (string)str_replace("'", "'", substr($sub['text'], 1, -1));
    }

	public function StringLiteral_SingleQuotedStringLiteral(&$result, $sub) {
		$result['val'] = (string)str_replace("'", "'", substr($sub['text'], 1, -1));
	}
	public function StringLiteral_DoubleQuotedStringLiteral(&$result, $sub) {
        $result['val'] = (string)str_replace('\"', '"', substr($sub['text'], 1, -1));
    }
}
?>
