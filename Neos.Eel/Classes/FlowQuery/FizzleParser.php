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
          | ( :Identifier S
              (
                  Operator:( 'instanceof' | '!instanceof' | PrefixMatch | SuffixMatch | SubstringMatch | ExactMatch | NotEqualMatch | LessThanOrEqualMatch | LessThanMatch | GreaterThanOrEqualMatch | GreaterThanMatch )
                  S ( Operand:StringLiteral | Operand:NumberLiteral | Operand:BooleanLiteral | Operand:UnquotedOperand ) S
              )?
          )
       )
  S ']' */
protected $match_AttributeFilter_typestack = array('AttributeFilter');
function match_AttributeFilter ($stack = array()) {
	$matchrule = "AttributeFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_146 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_146 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_146 = FALSE; break; }
		$_142 = NULL;
		do {
			$_140 = NULL;
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
				if( $_72 === TRUE ) { $_140 = TRUE; break; }
				$result = $res_55;
				$this->pos = $pos_55;
				$_138 = NULL;
				do {
					$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Identifier" );
					}
					else { $_138 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_138 = FALSE; break; }
					$res_137 = $result;
					$pos_137 = $this->pos;
					$_136 = NULL;
					do {
						$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
						$_117 = NULL;
						do {
							$_115 = NULL;
							do {
								$res_76 = $result;
								$pos_76 = $this->pos;
								if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_115 = TRUE; break;
								}
								$result = $res_76;
								$this->pos = $pos_76;
								$_113 = NULL;
								do {
									$res_78 = $result;
									$pos_78 = $this->pos;
									if (( $subres = $this->literal( '!instanceof' ) ) !== FALSE) {
										$result["text"] .= $subres;
										$_113 = TRUE; break;
									}
									$result = $res_78;
									$this->pos = $pos_78;
									$_111 = NULL;
									do {
										$res_80 = $result;
										$pos_80 = $this->pos;
										$matcher = 'match_'.'PrefixMatch'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_111 = TRUE; break;
										}
										$result = $res_80;
										$this->pos = $pos_80;
										$_109 = NULL;
										do {
											$res_82 = $result;
											$pos_82 = $this->pos;
											$matcher = 'match_'.'SuffixMatch'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_109 = TRUE; break;
											}
											$result = $res_82;
											$this->pos = $pos_82;
											$_107 = NULL;
											do {
												$res_84 = $result;
												$pos_84 = $this->pos;
												$matcher = 'match_'.'SubstringMatch'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_107 = TRUE; break;
												}
												$result = $res_84;
												$this->pos = $pos_84;
												$_105 = NULL;
												do {
													$res_86 = $result;
													$pos_86 = $this->pos;
													$matcher = 'match_'.'ExactMatch'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_105 = TRUE; break;
													}
													$result = $res_86;
													$this->pos = $pos_86;
													$_103 = NULL;
													do {
														$res_88 = $result;
														$pos_88 = $this->pos;
														$matcher = 'match_'.'NotEqualMatch'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_103 = TRUE; break;
														}
														$result = $res_88;
														$this->pos = $pos_88;
														$_101 = NULL;
														do {
															$res_90 = $result;
															$pos_90 = $this->pos;
															$matcher = 'match_'.'LessThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_101 = TRUE; break;
															}
															$result = $res_90;
															$this->pos = $pos_90;
															$_99 = NULL;
															do {
																$res_92 = $result;
																$pos_92 = $this->pos;
																$matcher = 'match_'.'LessThanMatch'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_99 = TRUE; break;
																}
																$result = $res_92;
																$this->pos = $pos_92;
																$_97 = NULL;
																do {
																	$res_94 = $result;
																	$pos_94 = $this->pos;
																	$matcher = 'match_'.'GreaterThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_97 = TRUE; break;
																	}
																	$result = $res_94;
																	$this->pos = $pos_94;
																	$matcher = 'match_'.'GreaterThanMatch'; $key = $matcher; $pos = $this->pos;
																	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																	if ($subres !== FALSE) {
																		$this->store( $result, $subres );
																		$_97 = TRUE; break;
																	}
																	$result = $res_94;
																	$this->pos = $pos_94;
																	$_97 = FALSE; break;
																}
																while(0);
																if( $_97 === TRUE ) { $_99 = TRUE; break; }
																$result = $res_92;
																$this->pos = $pos_92;
																$_99 = FALSE; break;
															}
															while(0);
															if( $_99 === TRUE ) { $_101 = TRUE; break; }
															$result = $res_90;
															$this->pos = $pos_90;
															$_101 = FALSE; break;
														}
														while(0);
														if( $_101 === TRUE ) { $_103 = TRUE; break; }
														$result = $res_88;
														$this->pos = $pos_88;
														$_103 = FALSE; break;
													}
													while(0);
													if( $_103 === TRUE ) { $_105 = TRUE; break; }
													$result = $res_86;
													$this->pos = $pos_86;
													$_105 = FALSE; break;
												}
												while(0);
												if( $_105 === TRUE ) { $_107 = TRUE; break; }
												$result = $res_84;
												$this->pos = $pos_84;
												$_107 = FALSE; break;
											}
											while(0);
											if( $_107 === TRUE ) { $_109 = TRUE; break; }
											$result = $res_82;
											$this->pos = $pos_82;
											$_109 = FALSE; break;
										}
										while(0);
										if( $_109 === TRUE ) { $_111 = TRUE; break; }
										$result = $res_80;
										$this->pos = $pos_80;
										$_111 = FALSE; break;
									}
									while(0);
									if( $_111 === TRUE ) { $_113 = TRUE; break; }
									$result = $res_78;
									$this->pos = $pos_78;
									$_113 = FALSE; break;
								}
								while(0);
								if( $_113 === TRUE ) { $_115 = TRUE; break; }
								$result = $res_76;
								$this->pos = $pos_76;
								$_115 = FALSE; break;
							}
							while(0);
							if( $_115 === FALSE) { $_117 = FALSE; break; }
							$_117 = TRUE; break;
						}
						while(0);
						if( $_117 === TRUE ) {
							$subres = $result; $result = array_pop($stack);
							$this->store( $result, $subres, 'Operator' );
						}
						if( $_117 === FALSE) {
							$result = array_pop($stack);
							$_136 = FALSE; break;
						}
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_136 = FALSE; break; }
						$_133 = NULL;
						do {
							$_131 = NULL;
							do {
								$res_120 = $result;
								$pos_120 = $this->pos;
								$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres, "Operand" );
									$_131 = TRUE; break;
								}
								$result = $res_120;
								$this->pos = $pos_120;
								$_129 = NULL;
								do {
									$res_122 = $result;
									$pos_122 = $this->pos;
									$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres, "Operand" );
										$_129 = TRUE; break;
									}
									$result = $res_122;
									$this->pos = $pos_122;
									$_127 = NULL;
									do {
										$res_124 = $result;
										$pos_124 = $this->pos;
										$matcher = 'match_'.'BooleanLiteral'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_127 = TRUE; break;
										}
										$result = $res_124;
										$this->pos = $pos_124;
										$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_127 = TRUE; break;
										}
										$result = $res_124;
										$this->pos = $pos_124;
										$_127 = FALSE; break;
									}
									while(0);
									if( $_127 === TRUE ) { $_129 = TRUE; break; }
									$result = $res_122;
									$this->pos = $pos_122;
									$_129 = FALSE; break;
								}
								while(0);
								if( $_129 === TRUE ) { $_131 = TRUE; break; }
								$result = $res_120;
								$this->pos = $pos_120;
								$_131 = FALSE; break;
							}
							while(0);
							if( $_131 === FALSE) { $_133 = FALSE; break; }
							$_133 = TRUE; break;
						}
						while(0);
						if( $_133 === FALSE) { $_136 = FALSE; break; }
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_136 = FALSE; break; }
						$_136 = TRUE; break;
					}
					while(0);
					if( $_136 === FALSE) {
						$result = $res_137;
						$this->pos = $pos_137;
						unset( $res_137 );
						unset( $pos_137 );
					}
					$_138 = TRUE; break;
				}
				while(0);
				if( $_138 === TRUE ) { $_140 = TRUE; break; }
				$result = $res_55;
				$this->pos = $pos_55;
				$_140 = FALSE; break;
			}
			while(0);
			if( $_140 === FALSE) { $_142 = FALSE; break; }
			$_142 = TRUE; break;
		}
		while(0);
		if( $_142 === FALSE) { $_146 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_146 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_146 = FALSE; break; }
		$_146 = TRUE; break;
	}
	while(0);
	if( $_146 === TRUE ) { return $this->finalise($result); }
	if( $_146 === FALSE) { return FALSE; }
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
