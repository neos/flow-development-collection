<?php
namespace TYPO3\Eel\FlowQuery;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/*!* !insert_autogen_warning */
/**
 * Fizzle parser
 *
 * This is the parser for a CSS-like selector language for Objects and TYPO3CR Nodes.
 * You can think of it as "Sizzle for PHP" (hence the name).
 */
class FizzleParser extends \TYPO3\Eel\AbstractParser {
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

Filter: ( :IdentifierFilter ) ? ( :PropertyNameFilter ) ?  ( AttributeFilters:AttributeFilter )*
	function AttributeFilters(&$result, $sub) {
		if (!isset($result['AttributeFilters'])) {
			$result['AttributeFilters'] = array();
		}
		$result['AttributeFilters'][] = $sub;
	}
	function PropertyNameFilter(&$result, $sub) {
		$result['PropertyNameFilter'] = $sub['Identifier'];
	}
	function IdentifierFilter(&$result, $sub) {
		$result['IdentifierFilter'] = substr($sub['text'], 1);
	}

PropertyNameFilter: Identifier
	function Identifier(&$result, $sub) {
		$result['Identifier'] = $sub['text'];
	}

IdentifierFilter: '#':ObjectIdentifier

AttributeFilter:
  '[' S
      (
          ( Operator:'instanceof' S ( Operand:StringLiteral | Operand:UnquotedOperand ) S )
          | ( :Identifier S
              (
                  Operator:( 'instanceof' | PrefixMatch | SuffixMatch | SubstringMatch | ExactMatch | NotEqualMatch )
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
?>