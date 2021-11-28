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

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/

/**
 * Fizzle parser
 *
 * This is the parser for a CSS-like selector language for Objects and Content Repository Nodes.
 * You can think of it as "Sizzle for PHP" (hence the name).
 *
 * @Neos\Flow\Annotations\Proxy(false)
 */
class FizzleParser extends \Neos\Eel\AbstractParser {
/* ObjectIdentifier: / [0-9a-zA-Z_-]+ / */
protected $match_ObjectIdentifier_typestack = array('ObjectIdentifier');
function match_ObjectIdentifier ($stack = array()) {
	$matchrule = "ObjectIdentifier"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ [0-9a-zA-Z_-]+ /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}




/* FilterGroup: :Filter ( S ',' S :Filter )* */
protected $match_FilterGroup_typestack = array('FilterGroup');
function match_FilterGroup ($stack = array()) {
	$matchrule = "FilterGroup"; $result = $this->construct($matchrule, $matchrule, null);
	$_8 = NULL;
	do {
		$matcher = 'match_'.'Filter'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "Filter" );
		}
		else { $_8 = FALSE; break; }
		while (true) {
			$res_7 = $result;
			$pos_7 = $this->pos;
			$_6 = NULL;
			do {
				$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_6 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_6 = FALSE; break; }
				$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_6 = FALSE; break; }
				$matcher = 'match_'.'Filter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "Filter" );
				}
				else { $_6 = FALSE; break; }
				$_6 = TRUE; break;
			}
			while(0);
			if( $_6 === FALSE) {
				$result = $res_7;
				$this->pos = $pos_7;
				unset( $res_7 );
				unset( $pos_7 );
				break;
			}
		}
		$_8 = TRUE; break;
	}
	while(0);
	if( $_8 === TRUE ) { return $this->finalise($result); }
	if( $_8 === FALSE) { return FALSE; }
}

function FilterGroup_Filter (&$result, $sub) {
		if (!isset($result['Filters'])) {
			$result['Filters'] = array();
		}
		$result['Filters'][] = $sub;
	}

/* Filter: ( PathFilter | IdentifierFilter | PropertyNameFilter )?  ( AttributeFilters:AttributeFilter )* */
protected $match_Filter_typestack = array('Filter');
function match_Filter ($stack = array()) {
	$matchrule = "Filter"; $result = $this->construct($matchrule, $matchrule, null);
	$_24 = NULL;
	do {
		$res_20 = $result;
		$pos_20 = $this->pos;
		$_19 = NULL;
		do {
			$_17 = NULL;
			do {
				$res_10 = $result;
				$pos_10 = $this->pos;
				$matcher = 'match_'.'PathFilter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres );
					$_17 = TRUE; break;
				}
				$result = $res_10;
				$this->pos = $pos_10;
				$_15 = NULL;
				do {
					$res_12 = $result;
					$pos_12 = $this->pos;
					$matcher = 'match_'.'IdentifierFilter'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_15 = TRUE; break;
					}
					$result = $res_12;
					$this->pos = $pos_12;
					$matcher = 'match_'.'PropertyNameFilter'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_15 = TRUE; break;
					}
					$result = $res_12;
					$this->pos = $pos_12;
					$_15 = FALSE; break;
				}
				while(0);
				if( $_15 === TRUE ) { $_17 = TRUE; break; }
				$result = $res_10;
				$this->pos = $pos_10;
				$_17 = FALSE; break;
			}
			while(0);
			if( $_17 === FALSE) { $_19 = FALSE; break; }
			$_19 = TRUE; break;
		}
		while(0);
		if( $_19 === FALSE) {
			$result = $res_20;
			$this->pos = $pos_20;
			unset( $res_20 );
			unset( $pos_20 );
		}
		while (true) {
			$res_23 = $result;
			$pos_23 = $this->pos;
			$_22 = NULL;
			do {
				$matcher = 'match_'.'AttributeFilter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "AttributeFilters" );
				}
				else { $_22 = FALSE; break; }
				$_22 = TRUE; break;
			}
			while(0);
			if( $_22 === FALSE) {
				$result = $res_23;
				$this->pos = $pos_23;
				unset( $res_23 );
				unset( $pos_23 );
				break;
			}
		}
		$_24 = TRUE; break;
	}
	while(0);
	if( $_24 === TRUE ) { return $this->finalise($result); }
	if( $_24 === FALSE) { return FALSE; }
}

function Filter_PathFilter (&$result, $sub) {
		$result['PathFilter'] = $sub['text'];
	}

function Filter_IdentifierFilter (&$result, $sub) {
		$result['IdentifierFilter'] = substr($sub['text'], 1);
	}

function Filter_PropertyNameFilter (&$result, $sub) {
		$result['PropertyNameFilter'] = $sub['Identifier'];
	}

function Filter_AttributeFilters (&$result, $sub) {
		if (!isset($result['AttributeFilters'])) {
			$result['AttributeFilters'] = array();
		}
		$result['AttributeFilters'][] = $sub;
	}

/* IdentifierFilter: '#':ObjectIdentifier */
protected $match_IdentifierFilter_typestack = array('IdentifierFilter');
function match_IdentifierFilter ($stack = array()) {
	$matchrule = "IdentifierFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_28 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '#') {
			$this->pos += 1;
			$result["text"] .= '#';
		}
		else { $_28 = FALSE; break; }
		$matcher = 'match_'.'ObjectIdentifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "ObjectIdentifier" );
		}
		else { $_28 = FALSE; break; }
		$_28 = TRUE; break;
	}
	while(0);
	if( $_28 === TRUE ) { return $this->finalise($result); }
	if( $_28 === FALSE) { return FALSE; }
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

/* PathFilter: ( '/' ( Identifier ( '/' Identifier )* )? ) | ( Identifier '/' Identifier ( '/' Identifier )* ) */
protected $match_PathFilter_typestack = array('PathFilter');
function match_PathFilter ($stack = array()) {
	$matchrule = "PathFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_51 = NULL;
	do {
		$res_31 = $result;
		$pos_31 = $this->pos;
		$_40 = NULL;
		do {
			if (substr($this->string,$this->pos,1) == '/') {
				$this->pos += 1;
				$result["text"] .= '/';
			}
			else { $_40 = FALSE; break; }
			$res_39 = $result;
			$pos_39 = $this->pos;
			$_38 = NULL;
			do {
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_38 = FALSE; break; }
				while (true) {
					$res_37 = $result;
					$pos_37 = $this->pos;
					$_36 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == '/') {
							$this->pos += 1;
							$result["text"] .= '/';
						}
						else { $_36 = FALSE; break; }
						$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_36 = FALSE; break; }
						$_36 = TRUE; break;
					}
					while(0);
					if( $_36 === FALSE) {
						$result = $res_37;
						$this->pos = $pos_37;
						unset( $res_37 );
						unset( $pos_37 );
						break;
					}
				}
				$_38 = TRUE; break;
			}
			while(0);
			if( $_38 === FALSE) {
				$result = $res_39;
				$this->pos = $pos_39;
				unset( $res_39 );
				unset( $pos_39 );
			}
			$_40 = TRUE; break;
		}
		while(0);
		if( $_40 === TRUE ) { $_51 = TRUE; break; }
		$result = $res_31;
		$this->pos = $pos_31;
		$_49 = NULL;
		do {
			$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_49 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '/') {
				$this->pos += 1;
				$result["text"] .= '/';
			}
			else { $_49 = FALSE; break; }
			$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_49 = FALSE; break; }
			while (true) {
				$res_48 = $result;
				$pos_48 = $this->pos;
				$_47 = NULL;
				do {
					if (substr($this->string,$this->pos,1) == '/') {
						$this->pos += 1;
						$result["text"] .= '/';
					}
					else { $_47 = FALSE; break; }
					$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_47 = FALSE; break; }
					$_47 = TRUE; break;
				}
				while(0);
				if( $_47 === FALSE) {
					$result = $res_48;
					$this->pos = $pos_48;
					unset( $res_48 );
					unset( $pos_48 );
					break;
				}
			}
			$_49 = TRUE; break;
		}
		while(0);
		if( $_49 === TRUE ) { $_51 = TRUE; break; }
		$result = $res_31;
		$this->pos = $pos_31;
		$_51 = FALSE; break;
	}
	while(0);
	if( $_51 === TRUE ) { return $this->finalise($result); }
	if( $_51 === FALSE) { return FALSE; }
}


/* AttributeFilter:
  '[' S
      (
          ( Operator:( 'instanceof' | '!instanceof' ) S ( Operand:StringLiteral | Operand:UnquotedOperand ) S )
          | ( :PropertyPath S
              (
                  Operator:( 'instanceof' | '!instanceof' | PrefixMatchInsensitive | PrefixMatch | SuffixMatchInsensitive | SuffixMatch | SubstringMatchInsensitivee | SubstringMatch | ExactMatchInsensitive | ExactMatch | NotEqualMatchInsensitive | NotEqualMatch | LessThanOrEqualMatch | LessThanMatch | GreaterThanOrEqualMatch | GreaterThanMatch )
                  S ( Operand:StringLiteral | Operand:NumberLiteral | Operand:BooleanLiteral | Operand:UnquotedOperand ) S
              )?
          )
       )
  S ']' */
protected $match_AttributeFilter_typestack = array('AttributeFilter');
function match_AttributeFilter ($stack = array()) {
	$matchrule = "AttributeFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_166 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_166 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_166 = FALSE; break; }
		$_162 = NULL;
		do {
			$_160 = NULL;
			do {
				$res_55 = $result;
				$pos_55 = $this->pos;
				$_72 = NULL;
				do {
					$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
					$_61 = NULL;
					do {
						$_59 = NULL;
						do {
							$res_56 = $result;
							$pos_56 = $this->pos;
							if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_59 = TRUE; break;
							}
							$result = $res_56;
							$this->pos = $pos_56;
							if (( $subres = $this->literal( '!instanceof' ) ) !== FALSE) {
								$result["text"] .= $subres;
								$_59 = TRUE; break;
							}
							$result = $res_56;
							$this->pos = $pos_56;
							$_59 = FALSE; break;
						}
						while(0);
						if( $_59 === FALSE) { $_61 = FALSE; break; }
						$_61 = TRUE; break;
					}
					while(0);
					if( $_61 === TRUE ) {
						$subres = $result; $result = array_pop($stack);
						$this->store( $result, $subres, 'Operator' );
					}
					if( $_61 === FALSE) {
						$result = array_pop($stack);
						$_72 = FALSE; break;
					}
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_72 = FALSE; break; }
					$_69 = NULL;
					do {
						$_67 = NULL;
						do {
							$res_64 = $result;
							$pos_64 = $this->pos;
							$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_67 = TRUE; break;
							}
							$result = $res_64;
							$this->pos = $pos_64;
							$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_67 = TRUE; break;
							}
							$result = $res_64;
							$this->pos = $pos_64;
							$_67 = FALSE; break;
						}
						while(0);
						if( $_67 === FALSE) { $_69 = FALSE; break; }
						$_69 = TRUE; break;
					}
					while(0);
					if( $_69 === FALSE) { $_72 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_72 = FALSE; break; }
					$_72 = TRUE; break;
				}
				while(0);
				if( $_72 === TRUE ) { $_160 = TRUE; break; }
				$result = $res_55;
				$this->pos = $pos_55;
				$_158 = NULL;
				do {
					$matcher = 'match_'.'PropertyPath'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "PropertyPath" );
					}
					else { $_158 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_158 = FALSE; break; }
					$res_157 = $result;
					$pos_157 = $this->pos;
					$_156 = NULL;
					do {
						$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
						$_137 = NULL;
						do {
							$_135 = NULL;
							do {
								$res_76 = $result;
								$pos_76 = $this->pos;
								if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_135 = TRUE; break;
								}
								$result = $res_76;
								$this->pos = $pos_76;
								$_133 = NULL;
								do {
									$res_78 = $result;
									$pos_78 = $this->pos;
									if (( $subres = $this->literal( '!instanceof' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_133 = TRUE; break;
									}
									$result = $res_78;
									$this->pos = $pos_78;
									$_131 = NULL;
									do {
										$res_80 = $result;
										$pos_80 = $this->pos;
										$matcher = 'match_'.'PrefixMatchInsensitive'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_131 = TRUE; break;
										}
										$result = $res_80;
										$this->pos = $pos_80;
										$_129 = NULL;
										do {
											$res_82 = $result;
											$pos_82 = $this->pos;
											$matcher = 'match_'.'PrefixMatch'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_129 = TRUE; break;
											}
											$result = $res_82;
											$this->pos = $pos_82;
											$_127 = NULL;
											do {
												$res_84 = $result;
												$pos_84 = $this->pos;
												$matcher = 'match_'.'SuffixMatchInsensitive'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_127 = TRUE; break;
												}
												$result = $res_84;
												$this->pos = $pos_84;
												$_125 = NULL;
												do {
													$res_86 = $result;
													$pos_86 = $this->pos;
													$matcher = 'match_'.'SuffixMatch'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_125 = TRUE; break;
													}
													$result = $res_86;
													$this->pos = $pos_86;
													$_123 = NULL;
													do {
														$res_88 = $result;
														$pos_88 = $this->pos;
														$matcher = 'match_'.'SubstringMatchInsensitivee'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_123 = TRUE; break;
														}
														$result = $res_88;
														$this->pos = $pos_88;
														$_121 = NULL;
														do {
															$res_90 = $result;
															$pos_90 = $this->pos;
															$matcher = 'match_'.'SubstringMatch'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_121 = TRUE; break;
															}
															$result = $res_90;
															$this->pos = $pos_90;
															$_119 = NULL;
															do {
																$res_92 = $result;
																$pos_92 = $this->pos;
																$matcher = 'match_'.'ExactMatchInsensitive'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_119 = TRUE; break;
																}
																$result = $res_92;
																$this->pos = $pos_92;
																$_117 = NULL;
																do {
																	$res_94 = $result;
																	$pos_94 = $this->pos;
																	$matcher = 'match_'.'ExactMatch'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_117 = TRUE; break;
																	}
																	$result = $res_94;
																	$this->pos = $pos_94;
																	$_115 = NULL;
																	do {
																		$res_96 = $result;
																		$pos_96 = $this->pos;
																		$matcher = 'match_'.'NotEqualMatchInsensitive'; $key = $matcher; $pos = $this->pos;
																		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																		if ($subres !== FALSE) {
																			$this->store( $result, $subres );
																			$_115 = TRUE; break;
																		}
																		$result = $res_96;
																		$this->pos = $pos_96;
																		$_113 = NULL;
																		do {
																			$res_98 = $result;
																			$pos_98 = $this->pos;
																			$matcher = 'match_'.'NotEqualMatch'; $key = $matcher; $pos = $this->pos;
																			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																			if ($subres !== FALSE) {
																				$this->store( $result, $subres );
																				$_113 = TRUE; break;
																			}
																			$result = $res_98;
																			$this->pos = $pos_98;
																			$_111 = NULL;
																			do {
																				$res_100 = $result;
																				$pos_100 = $this->pos;
																				$matcher = 'match_'.'LessThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
																				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																				if ($subres !== FALSE) {
																					$this->store( $result, $subres );
																					$_111 = TRUE; break;
																				}
																				$result = $res_100;
																				$this->pos = $pos_100;
																				$_109 = NULL;
																				do {
																					$res_102 = $result;
																					$pos_102 = $this->pos;
																					$matcher = 'match_'.'LessThanMatch'; $key = $matcher; $pos = $this->pos;
																					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																					if ($subres !== FALSE) {
																						$this->store( $result, $subres );
																						$_109 = TRUE; break;
																					}
																					$result = $res_102;
																					$this->pos = $pos_102;
																					$_107 = NULL;
																					do {
																						$res_104 = $result;
																						$pos_104 = $this->pos;
																						$matcher = 'match_'.'GreaterThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
																						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																						if ($subres !== FALSE) {
																							$this->store( $result, $subres );
																							$_107 = TRUE; break;
																						}
																						$result = $res_104;
																						$this->pos = $pos_104;
																						$matcher = 'match_'.'GreaterThanMatch'; $key = $matcher; $pos = $this->pos;
																						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																						if ($subres !== FALSE) {
																							$this->store( $result, $subres );
																							$_107 = TRUE; break;
																						}
																						$result = $res_104;
																						$this->pos = $pos_104;
																						$_107 = FALSE; break;
																					}
																					while(0);
																					if( $_107 === TRUE ) {
																						$_109 = TRUE; break;
																					}
																					$result = $res_102;
																					$this->pos = $pos_102;
																					$_109 = FALSE; break;
																				}
																				while(0);
																				if( $_109 === TRUE ) {
																					$_111 = TRUE; break;
																				}
																				$result = $res_100;
																				$this->pos = $pos_100;
																				$_111 = FALSE; break;
																			}
																			while(0);
																			if( $_111 === TRUE ) {
																				$_113 = TRUE; break;
																			}
																			$result = $res_98;
																			$this->pos = $pos_98;
																			$_113 = FALSE; break;
																		}
																		while(0);
																		if( $_113 === TRUE ) { $_115 = TRUE; break; }
																		$result = $res_96;
																		$this->pos = $pos_96;
																		$_115 = FALSE; break;
																	}
																	while(0);
																	if( $_115 === TRUE ) { $_117 = TRUE; break; }
																	$result = $res_94;
																	$this->pos = $pos_94;
																	$_117 = FALSE; break;
																}
																while(0);
																if( $_117 === TRUE ) { $_119 = TRUE; break; }
																$result = $res_92;
																$this->pos = $pos_92;
																$_119 = FALSE; break;
															}
															while(0);
															if( $_119 === TRUE ) { $_121 = TRUE; break; }
															$result = $res_90;
															$this->pos = $pos_90;
															$_121 = FALSE; break;
														}
														while(0);
														if( $_121 === TRUE ) { $_123 = TRUE; break; }
														$result = $res_88;
														$this->pos = $pos_88;
														$_123 = FALSE; break;
													}
													while(0);
													if( $_123 === TRUE ) { $_125 = TRUE; break; }
													$result = $res_86;
													$this->pos = $pos_86;
													$_125 = FALSE; break;
												}
												while(0);
												if( $_125 === TRUE ) { $_127 = TRUE; break; }
												$result = $res_84;
												$this->pos = $pos_84;
												$_127 = FALSE; break;
											}
											while(0);
											if( $_127 === TRUE ) { $_129 = TRUE; break; }
											$result = $res_82;
											$this->pos = $pos_82;
											$_129 = FALSE; break;
										}
										while(0);
										if( $_129 === TRUE ) { $_131 = TRUE; break; }
										$result = $res_80;
										$this->pos = $pos_80;
										$_131 = FALSE; break;
									}
									while(0);
									if( $_131 === TRUE ) { $_133 = TRUE; break; }
									$result = $res_78;
									$this->pos = $pos_78;
									$_133 = FALSE; break;
								}
								while(0);
								if( $_133 === TRUE ) { $_135 = TRUE; break; }
								$result = $res_76;
								$this->pos = $pos_76;
								$_135 = FALSE; break;
							}
							while(0);
							if( $_135 === FALSE) { $_137 = FALSE; break; }
							$_137 = TRUE; break;
						}
						while(0);
						if( $_137 === TRUE ) {
							$subres = $result; $result = array_pop($stack);
							$this->store( $result, $subres, 'Operator' );
						}
						if( $_137 === FALSE) {
							$result = array_pop($stack);
							$_156 = FALSE; break;
						}
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_156 = FALSE; break; }
						$_153 = NULL;
						do {
							$_151 = NULL;
							do {
								$res_140 = $result;
								$pos_140 = $this->pos;
								$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres, "Operand" );
									$_151 = TRUE; break;
								}
								$result = $res_140;
								$this->pos = $pos_140;
								$_149 = NULL;
								do {
									$res_142 = $result;
									$pos_142 = $this->pos;
									$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres, "Operand" );
										$_149 = TRUE; break;
									}
									$result = $res_142;
									$this->pos = $pos_142;
									$_147 = NULL;
									do {
										$res_144 = $result;
										$pos_144 = $this->pos;
										$matcher = 'match_'.'BooleanLiteral'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_147 = TRUE; break;
										}
										$result = $res_144;
										$this->pos = $pos_144;
										$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_147 = TRUE; break;
										}
										$result = $res_144;
										$this->pos = $pos_144;
										$_147 = FALSE; break;
									}
									while(0);
									if( $_147 === TRUE ) { $_149 = TRUE; break; }
									$result = $res_142;
									$this->pos = $pos_142;
									$_149 = FALSE; break;
								}
								while(0);
								if( $_149 === TRUE ) { $_151 = TRUE; break; }
								$result = $res_140;
								$this->pos = $pos_140;
								$_151 = FALSE; break;
							}
							while(0);
							if( $_151 === FALSE) { $_153 = FALSE; break; }
							$_153 = TRUE; break;
						}
						while(0);
						if( $_153 === FALSE) { $_156 = FALSE; break; }
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_156 = FALSE; break; }
						$_156 = TRUE; break;
					}
					while(0);
					if( $_156 === FALSE) {
						$result = $res_157;
						$this->pos = $pos_157;
						unset( $res_157 );
						unset( $pos_157 );
					}
					$_158 = TRUE; break;
				}
				while(0);
				if( $_158 === TRUE ) { $_160 = TRUE; break; }
				$result = $res_55;
				$this->pos = $pos_55;
				$_160 = FALSE; break;
			}
			while(0);
			if( $_160 === FALSE) { $_162 = FALSE; break; }
			$_162 = TRUE; break;
		}
		while(0);
		if( $_162 === FALSE) { $_166 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_166 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_166 = FALSE; break; }
		$_166 = TRUE; break;
	}
	while(0);
	if( $_166 === TRUE ) { return $this->finalise($result); }
	if( $_166 === FALSE) { return FALSE; }
}

function AttributeFilter__construct (&$result) {
	  $result['Operator'] = NULL;
	  $result['PropertyPath'] = NULL;
	  $result['Identifier'] = NULL;
	}

function AttributeFilter_PropertyPath (&$result, $sub) {
	  $result['PropertyPath'] = $sub['text'];
	  $result['Identifier'] = $result['PropertyPath'];
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

/* PrefixMatchInsensitive: '^=~' */
protected $match_PrefixMatchInsensitive_typestack = array('PrefixMatchInsensitive');
function match_PrefixMatchInsensitive ($stack = array()) {
	$matchrule = "PrefixMatchInsensitive"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '^=~' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
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


/* SuffixMatchInsensitive: '$=~' */
protected $match_SuffixMatchInsensitive_typestack = array('SuffixMatchInsensitive');
function match_SuffixMatchInsensitive ($stack = array()) {
	$matchrule = "SuffixMatchInsensitive"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '$=~' ) ) !== FALSE) {
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


/* SubstringMatchInsensitivee: '*=~' */
protected $match_SubstringMatchInsensitivee_typestack = array('SubstringMatchInsensitivee');
function match_SubstringMatchInsensitivee ($stack = array()) {
	$matchrule = "SubstringMatchInsensitivee"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '*=~' ) ) !== FALSE) {
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


/* ExactMatchInsensitive: '=~' */
protected $match_ExactMatchInsensitive_typestack = array('ExactMatchInsensitive');
function match_ExactMatchInsensitive ($stack = array()) {
	$matchrule = "ExactMatchInsensitive"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '=~' ) ) !== FALSE) {
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


/* NotEqualMatchInsensitive: '!=~' */
protected $match_NotEqualMatchInsensitive_typestack = array('NotEqualMatchInsensitive');
function match_NotEqualMatchInsensitive ($stack = array()) {
	$matchrule = "NotEqualMatchInsensitive"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '!=~' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* NotEqualMatch: '!=' */
protected $match_NotEqualMatch_typestack = array('NotEqualMatch');
function match_NotEqualMatch ($stack = array()) {
	$matchrule = "NotEqualMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* LessThanOrEqualMatch: '<=' */
protected $match_LessThanOrEqualMatch_typestack = array('LessThanOrEqualMatch');
function match_LessThanOrEqualMatch ($stack = array()) {
	$matchrule = "LessThanOrEqualMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '<=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* LessThanMatch: '<' */
protected $match_LessThanMatch_typestack = array('LessThanMatch');
function match_LessThanMatch ($stack = array()) {
	$matchrule = "LessThanMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (substr($this->string,$this->pos,1) == '<') {
		$this->pos += 1;
		$result["text"] .= '<';
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* GreaterThanOrEqualMatch: '>=' */
protected $match_GreaterThanOrEqualMatch_typestack = array('GreaterThanOrEqualMatch');
function match_GreaterThanOrEqualMatch ($stack = array()) {
	$matchrule = "GreaterThanOrEqualMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '>=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* GreaterThanMatch: '>' */
protected $match_GreaterThanMatch_typestack = array('GreaterThanMatch');
function match_GreaterThanMatch ($stack = array()) {
	$matchrule = "GreaterThanMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (substr($this->string,$this->pos,1) == '>') {
		$this->pos += 1;
		$result["text"] .= '>';
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
