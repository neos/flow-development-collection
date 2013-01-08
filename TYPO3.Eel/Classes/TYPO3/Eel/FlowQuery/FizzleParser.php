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

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/

/**
 * Fizzle parser
 *
 * This is the parser for a CSS-like selector language for Objects and TYPO3CR Nodes.
 * You can think of it as "Sizzle for PHP" (hence the name).
 */
class FizzleParser extends \TYPO3\Eel\AbstractParser {
/* FilterGroup: :Filter ( S ',' S :Filter )* */
protected $match_FilterGroup_typestack = array('FilterGroup');
function match_FilterGroup ($stack = array()) {
	$matchrule = "FilterGroup"; $result = $this->construct($matchrule, $matchrule, null);
	$_7 = NULL;
	do {
		$matcher = 'match_'.'Filter'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "Filter" );
		}
		else { $_7 = FALSE; break; }
		while (true) {
			$res_6 = $result;
			$pos_6 = $this->pos;
			$_5 = NULL;
			do {
				$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_5 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_5 = FALSE; break; }
				$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_5 = FALSE; break; }
				$matcher = 'match_'.'Filter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Filter" );
				}
				else { $_5 = FALSE; break; }
				$_5 = TRUE; break;
			}
			while(0);
			if( $_5 === FALSE) {
				$result = $res_6;
				$this->pos = $pos_6;
				unset( $res_6 );
				unset( $pos_6 );
				break;
			}
		}
		$_7 = TRUE; break;
	}
	while(0);
	if( $_7 === TRUE ) { return $this->finalise($result); }
	if( $_7 === FALSE) { return FALSE; }
}

function FilterGroup_Filter (&$result, $sub) {
		if (!isset($result['Filters'])) {
			$result['Filters'] = array();
		}
		$result['Filters'][] = $sub;
	}

/* Filter: ( :PropertyNameFilter ) ?  ( AttributeFilters:AttributeFilter )* */
protected $match_Filter_typestack = array('Filter');
function match_Filter ($stack = array()) {
	$matchrule = "Filter"; $result = $this->construct($matchrule, $matchrule, null);
	$_15 = NULL;
	do {
		$res_11 = $result;
		$pos_11 = $this->pos;
		$_10 = NULL;
		do {
			$matcher = 'match_'.'PropertyNameFilter'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "PropertyNameFilter" );
			}
			else { $_10 = FALSE; break; }
			$_10 = TRUE; break;
		}
		while(0);
		if( $_10 === FALSE) {
			$result = $res_11;
			$this->pos = $pos_11;
			unset( $res_11 );
			unset( $pos_11 );
		}
		while (true) {
			$res_14 = $result;
			$pos_14 = $this->pos;
			$_13 = NULL;
			do {
				$matcher = 'match_'.'AttributeFilter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "AttributeFilters" );
				}
				else { $_13 = FALSE; break; }
				$_13 = TRUE; break;
			}
			while(0);
			if( $_13 === FALSE) {
				$result = $res_14;
				$this->pos = $pos_14;
				unset( $res_14 );
				unset( $pos_14 );
				break;
			}
		}
		$_15 = TRUE; break;
	}
	while(0);
	if( $_15 === TRUE ) { return $this->finalise($result); }
	if( $_15 === FALSE) { return FALSE; }
}

function Filter_AttributeFilters (&$result, $sub) {
		if (!isset($result['AttributeFilters'])) {
			$result['AttributeFilters'] = array();
		}
		$result['AttributeFilters'][] = $sub;
	}

function Filter_PropertyNameFilter (&$result, $sub) {
		$result['PropertyNameFilter'] = $sub['Identifier'];
	}

/* PropertyNameFilter: Identifier */
protected $match_PropertyNameFilter_typestack = array('PropertyNameFilter');
function match_PropertyNameFilter ($stack = array()) {
	$matchrule = "PropertyNameFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
	if ($subres !== FALSE) {
		$this->store( $result, $subres );
		return $this->finalise($result);
	}
	else { return FALSE; }
}

function PropertyNameFilter_Identifier (&$result, $sub) {
		$result['Identifier'] = $sub['text'];
	}

/* AttributeFilter:
  '[' S
      (
          ( Operator:'instanceof' S ( Operand:StringLiteral | Operand:UnquotedOperand ) S )
          | ( :Identifier S
              (
                  Operator:( PrefixMatch | SuffixMatch | SubstringMatch | ExactMatch )
                  S ( Operand:StringLiteral | Operand:NumberLiteral | Operand:BooleanLiteral | Operand:UnquotedOperand ) S
              )?
          )
       )
  S ']' */
protected $match_AttributeFilter_typestack = array('AttributeFilter');
function match_AttributeFilter ($stack = array()) {
	$matchrule = "AttributeFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_77 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_77 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_77 = FALSE; break; }
		$_73 = NULL;
		do {
			$_71 = NULL;
			do {
				$res_20 = $result;
				$pos_20 = $this->pos;
				$_31 = NULL;
				do {
					$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
					if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$subres = $result; $result = array_pop($stack);
						$this->store( $result, $subres, 'Operator' );
					}
					else {
						$result = array_pop($stack);
						$_31 = FALSE; break;
					}
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_31 = FALSE; break; }
					$_28 = NULL;
					do {
						$_26 = NULL;
						do {
							$res_23 = $result;
							$pos_23 = $this->pos;
							$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_26 = TRUE; break;
							}
							$result = $res_23;
							$this->pos = $pos_23;
							$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_26 = TRUE; break;
							}
							$result = $res_23;
							$this->pos = $pos_23;
							$_26 = FALSE; break;
						}
						while(0);
						if( $_26 === FALSE) { $_28 = FALSE; break; }
						$_28 = TRUE; break;
					}
					while(0);
					if( $_28 === FALSE) { $_31 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_31 = FALSE; break; }
					$_31 = TRUE; break;
				}
				while(0);
				if( $_31 === TRUE ) { $_71 = TRUE; break; }
				$result = $res_20;
				$this->pos = $pos_20;
				$_69 = NULL;
				do {
					$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Identifier" );
					}
					else { $_69 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_69 = FALSE; break; }
					$res_68 = $result;
					$pos_68 = $this->pos;
					$_67 = NULL;
					do {
						$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
						$_48 = NULL;
						do {
							$_46 = NULL;
							do {
								$res_35 = $result;
								$pos_35 = $this->pos;
								$matcher = 'match_'.'PrefixMatch'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_46 = TRUE; break;
								}
								$result = $res_35;
								$this->pos = $pos_35;
								$_44 = NULL;
								do {
									$res_37 = $result;
									$pos_37 = $this->pos;
									$matcher = 'match_'.'SuffixMatch'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_44 = TRUE; break;
									}
									$result = $res_37;
									$this->pos = $pos_37;
									$_42 = NULL;
									do {
										$res_39 = $result;
										$pos_39 = $this->pos;
										$matcher = 'match_'.'SubstringMatch'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_42 = TRUE; break;
										}
										$result = $res_39;
										$this->pos = $pos_39;
										$matcher = 'match_'.'ExactMatch'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_42 = TRUE; break;
										}
										$result = $res_39;
										$this->pos = $pos_39;
										$_42 = FALSE; break;
									}
									while(0);
									if( $_42 === TRUE ) { $_44 = TRUE; break; }
									$result = $res_37;
									$this->pos = $pos_37;
									$_44 = FALSE; break;
								}
								while(0);
								if( $_44 === TRUE ) { $_46 = TRUE; break; }
								$result = $res_35;
								$this->pos = $pos_35;
								$_46 = FALSE; break;
							}
							while(0);
							if( $_46 === FALSE) { $_48 = FALSE; break; }
							$_48 = TRUE; break;
						}
						while(0);
						if( $_48 === TRUE ) {
							$subres = $result; $result = array_pop($stack);
							$this->store( $result, $subres, 'Operator' );
						}
						if( $_48 === FALSE) {
							$result = array_pop($stack);
							$_67 = FALSE; break;
						}
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_67 = FALSE; break; }
						$_64 = NULL;
						do {
							$_62 = NULL;
							do {
								$res_51 = $result;
								$pos_51 = $this->pos;
								$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres, "Operand" );
									$_62 = TRUE; break;
								}
								$result = $res_51;
								$this->pos = $pos_51;
								$_60 = NULL;
								do {
									$res_53 = $result;
									$pos_53 = $this->pos;
									$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres, "Operand" );
										$_60 = TRUE; break;
									}
									$result = $res_53;
									$this->pos = $pos_53;
									$_58 = NULL;
									do {
										$res_55 = $result;
										$pos_55 = $this->pos;
										$matcher = 'match_'.'BooleanLiteral'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_58 = TRUE; break;
										}
										$result = $res_55;
										$this->pos = $pos_55;
										$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_58 = TRUE; break;
										}
										$result = $res_55;
										$this->pos = $pos_55;
										$_58 = FALSE; break;
									}
									while(0);
									if( $_58 === TRUE ) { $_60 = TRUE; break; }
									$result = $res_53;
									$this->pos = $pos_53;
									$_60 = FALSE; break;
								}
								while(0);
								if( $_60 === TRUE ) { $_62 = TRUE; break; }
								$result = $res_51;
								$this->pos = $pos_51;
								$_62 = FALSE; break;
							}
							while(0);
							if( $_62 === FALSE) { $_64 = FALSE; break; }
							$_64 = TRUE; break;
						}
						while(0);
						if( $_64 === FALSE) { $_67 = FALSE; break; }
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_67 = FALSE; break; }
						$_67 = TRUE; break;
					}
					while(0);
					if( $_67 === FALSE) {
						$result = $res_68;
						$this->pos = $pos_68;
						unset( $res_68 );
						unset( $pos_68 );
					}
					$_69 = TRUE; break;
				}
				while(0);
				if( $_69 === TRUE ) { $_71 = TRUE; break; }
				$result = $res_20;
				$this->pos = $pos_20;
				$_71 = FALSE; break;
			}
			while(0);
			if( $_71 === FALSE) { $_73 = FALSE; break; }
			$_73 = TRUE; break;
		}
		while(0);
		if( $_73 === FALSE) { $_77 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_77 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_77 = FALSE; break; }
		$_77 = TRUE; break;
	}
	while(0);
	if( $_77 === TRUE ) { return $this->finalise($result); }
	if( $_77 === FALSE) { return FALSE; }
}

function AttributeFilter__construct (&$result) {
		$result['Operator'] = NULL;
		$result['Identifier'] = NULL;
	}

function AttributeFilter_Identifier (&$result, $sub) {
		$result['Identifier'] = $sub['text'];
	}

function AttributeFilter_Operator (&$result, $sub) {
		$result['Operator'] = $sub['text'];
	}

function AttributeFilter_Operand (&$result, $sub) {
		$result['Operand'] = $sub['val'];
	}

/* UnquotedOperand: / [^"'\[\]\s]+ / */
protected $match_UnquotedOperand_typestack = array('UnquotedOperand');
function match_UnquotedOperand ($stack = array()) {
	$matchrule = "UnquotedOperand"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ [^"\'\[\]\s]+ /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}

function UnquotedOperand__finalise (&$self) {
		$self['val'] = $self['text'];
	}

/* PrefixMatch: '^=' */
protected $match_PrefixMatch_typestack = array('PrefixMatch');
function match_PrefixMatch ($stack = array()) {
	$matchrule = "PrefixMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '^=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* SuffixMatch: '$=' */
protected $match_SuffixMatch_typestack = array('SuffixMatch');
function match_SuffixMatch ($stack = array()) {
	$matchrule = "SuffixMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '$=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* SubstringMatch: '*=' */
protected $match_SubstringMatch_typestack = array('SubstringMatch');
function match_SubstringMatch ($stack = array()) {
	$matchrule = "SubstringMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '*=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* ExactMatch: '=' */
protected $match_ExactMatch_typestack = array('ExactMatch');
function match_ExactMatch ($stack = array()) {
	$matchrule = "ExactMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (substr($this->string,$this->pos,1) == '=') {
		$this->pos += 1;
		$result["text"] .= '=';
		return $this->finalise($result);
	}
	else { return FALSE; }
}




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