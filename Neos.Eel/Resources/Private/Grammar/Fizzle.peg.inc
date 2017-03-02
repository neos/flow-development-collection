<?php
namespace Neos\Eel\FlowQuery;
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

/*!* !insert_autogen_warning */
/**
 * Fizzle parser
 *
 * This is the parser for a CSS-like selector language for Objects and Content Repository Nodes.
 * You can think of it as "Sizzle for PHP" (hence the name).
 *
 * @Neos\Flow\Annotations\Proxy(false)
 */
class FizzleParser extends \Neos\Eel\AbstractParser {
/*!* FizzleTypes

ObjectIdentifier: / [0-9a-zA-Z_-]+ /

*/

/*!* FilterExpressions

FilterGroup: :Filter ( S ',' S :Filter )*
	function Filter(&$result, $sub) {
		if (!isset($result['Filters'])) {
			$result['Filters'] = array();
		}
		$result['Filters'][] = $sub;
	}

Filter: ( PathFilter | IdentifierFilter | PropertyNameFilter )?  ( AttributeFilters:AttributeFilter )*
	function PathFilter(&$result, $sub) {
		$result['PathFilter'] = $sub['text'];
	}
	function IdentifierFilter(&$result, $sub) {
		$result['IdentifierFilter'] = substr($sub['text'], 1);
	}
	function PropertyNameFilter(&$result, $sub) {
		$result['PropertyNameFilter'] = $sub['Identifier'];
	}
	function AttributeFilters(&$result, $sub) {
		if (!isset($result['AttributeFilters'])) {
			$result['AttributeFilters'] = array();
		}
		$result['AttributeFilters'][] = $sub;
	}

IdentifierFilter: '#':ObjectIdentifier

PropertyNameFilter: Identifier
	function Identifier(&$result, $sub) {
		$result['Identifier'] = $sub['text'];
	}

PathFilter: ( '/' ( Identifier ( '/' Identifier )* )? ) | ( Identifier '/' Identifier ( '/' Identifier )* )

AttributeFilter:
  '[' S
      (
          ( Operator:( 'instanceof' | '!instanceof' ) S ( Operand:StringLiteral | Operand:UnquotedOperand ) S )
          | ( :Identifier S
              (
                  Operator:( 'instanceof' | '!instanceof' | PrefixMatch | SuffixMatch | SubstringMatch | ExactMatch | NotEqualMatch | LessThanOrEqualMatch | LessThanMatch | GreaterThanOrEqualMatch | GreaterThanMatch )
                  S ( Operand:StringLiteral | Operand:NumberLiteral | Operand:BooleanLiteral | Operand:UnquotedOperand ) S
              )?
          )
       )
  S ']'
	function __construct(&$result) {
		$result['Operator'] = NULL;
		$result['Identifier'] = NULL;
	}
	function Identifier(&$result, $sub) {
		$result['Identifier'] = $sub['text'];
	}
	function Operator(&$result, $sub) {
		$result['Operator'] = $sub['text'];
	}
	function Operand(&$result, $sub) {
		$result['Operand'] = $sub['val'];
	}

UnquotedOperand: / [^"'\[\]\s]+ /
	function __finalise(&$self) {
		$self['val'] = $self['text'];
	}

PrefixMatch: '^='
SuffixMatch: '$='
SubstringMatch: '*='
ExactMatch: '='
NotEqualMatch: '!='
LessThanOrEqualMatch: '<='
LessThanMatch: '<'
GreaterThanOrEqualMatch: '>='
GreaterThanMatch: '>'

 */

	static public function parseFilterGroup($filter) {
		$parser = new FizzleParser($filter);
		$parsedFilter = $parser->match_FilterGroup();
		if ($parser->pos !== strlen($filter)) {
			throw new FizzleException(sprintf('The Selector "%s" could not be parsed. Error at character %d.', $filter, $parser->pos+1), 1327649317);
		}
		return $parsedFilter;
	}

	function BooleanLiteral__finalise(&$self) {
		$self['val'] = strtolower($self['text']) === 'true';
	}

	public function NumberLiteral__finalise(&$self) {
		if (isset($self['dec'])) {
			$self['val'] = (float)($self['text']);
		} else {
			$self['val'] = (integer)$self['text'];
		}
	}
}
