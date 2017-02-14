<?php
namespace Neos\Eel;
// @codingStandardsIgnoreFile

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once __DIR__ . '/../../../Resources/Private/PHP/php-peg/Parser.php';

/*!* !insert_autogen_warning */

/**
 * This Abstract Parser class contains definitions for absolutely basic types,
 * like quoted strings or identifiers
 *
 * @Neos\Flow\Annotations\Proxy(false)
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

    public function StringLiteral_DoubleQuotedStringLiteral(&$result, $sub)
    {
        $result['val'] = (string)str_replace('\"', '"', substr($sub['text'], 1, -1));
    }
}
