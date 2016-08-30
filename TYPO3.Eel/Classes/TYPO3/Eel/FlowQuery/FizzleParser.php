<?php
namespace TYPO3\Eel\FlowQuery;

/*
 * This file is part of the TYPO3.Eel package.
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
use TYPO3\Eel\AbstractParser;

/**
 * Fizzle parser
 *
 * This is the parser for a CSS-like selector language for Objects and TYPO3CR Nodes.
 * You can think of it as "Sizzle for PHP" (hence the name).
 */
class FizzleParser extends AbstractParser {
/* ObjectIdentifier: / [0-9a-zA-Z_-]+ / */
protected $match_ObjectIdentifier_typestack = ['ObjectIdentifier'];
function match_ObjectIdentifier ($stack = []) {
	$matchrule = "ObjectIdentifier"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ [0-9a-zA-Z_-]+ /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}




/* FilterGroup: :Filter ( S ',' S :Filter )* */
protected $match_FilterGroup_typestack = ['FilterGroup'];
function match_FilterGroup ($stack = []) {
	$matchrule = "FilterGroup"; $result = $this->construct($matchrule, $matchrule, null);
	$_8 = NULL;
	do {
		$matcher = 'match_'.'Filter'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_6 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_6 = FALSE; break; }
				$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_6 = FALSE; break; }
				$matcher = 'match_'.'Filter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
			$result['Filters'] = [];
		}
		$result['Filters'][] = $sub;
	}

/* Filter: ( PathFilter | IdentifierFilter | PropertyNameFilter )?  ( AttributeFilters:AttributeFilter )* */
protected $match_Filter_typestack = ['Filter'];
function match_Filter ($stack = []) {
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
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_15 = TRUE; break;
					}
					$result = $res_12;
					$this->pos = $pos_12;
					$matcher = 'match_'.'PropertyNameFilter'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
			$result['AttributeFilters'] = [];
		}
		$result['AttributeFilters'][] = $sub;
	}

/* IdentifierFilter: '#':ObjectIdentifier */
protected $match_IdentifierFilter_typestack = ['IdentifierFilter'];
function match_IdentifierFilter ($stack = []) {
	$matchrule = "IdentifierFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_28 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '#') {
			$this->pos += 1;
			$result["text"] .= '#';
		}
		else { $_28 = FALSE; break; }
		$matcher = 'match_'.'ObjectIdentifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
protected $match_PropertyNameFilter_typestack = ['PropertyNameFilter'];
function match_PropertyNameFilter ($stack = []) {
	$matchrule = "PropertyNameFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
protected $match_PathFilter_typestack = ['PathFilter'];
function match_PathFilter ($stack = []) {
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
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
			if ($subres !== FALSE) { $this->store( $result, $subres ); }
			else { $_49 = FALSE; break; }
			if (substr($this->string,$this->pos,1) == '/') {
				$this->pos += 1;
				$result["text"] .= '/';
			}
			else { $_49 = FALSE; break; }
			$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
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
          ( Operator:'instanceof' S ( Operand:StringLiteral | Operand:UnquotedOperand ) S )
          | ( :Identifier S
              (
                  Operator:( 'instanceof' | PrefixMatch | SuffixMatch | SubstringMatch | ExactMatch | NotEqualMatch | LessThanOrEqualMatch | LessThanMatch | GreaterThanOrEqualMatch | GreaterThanMatch )
                  S ( Operand:StringLiteral | Operand:NumberLiteral | Operand:BooleanLiteral | Operand:UnquotedOperand ) S
              )?
          )
       )
  S ']' */
protected $match_AttributeFilter_typestack = ['AttributeFilter'];
function match_AttributeFilter ($stack = []) {
	$matchrule = "AttributeFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_136 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_136 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_136 = FALSE; break; }
		$_132 = NULL;
		do {
			$_130 = NULL;
			do {
				$res_55 = $result;
				$pos_55 = $this->pos;
				$_66 = NULL;
				do {
					$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
					if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$subres = $result; $result = array_pop($stack);
						$this->store( $result, $subres, 'Operator' );
					}
					else {
						$result = array_pop($stack);
						$_66 = FALSE; break;
					}
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_66 = FALSE; break; }
					$_63 = NULL;
					do {
						$_61 = NULL;
						do {
							$res_58 = $result;
							$pos_58 = $this->pos;
							$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_61 = TRUE; break;
							}
							$result = $res_58;
							$this->pos = $pos_58;
							$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_61 = TRUE; break;
							}
							$result = $res_58;
							$this->pos = $pos_58;
							$_61 = FALSE; break;
						}
						while(0);
						if( $_61 === FALSE) { $_63 = FALSE; break; }
						$_63 = TRUE; break;
					}
					while(0);
					if( $_63 === FALSE) { $_66 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_66 = FALSE; break; }
					$_66 = TRUE; break;
				}
				while(0);
				if( $_66 === TRUE ) { $_130 = TRUE; break; }
				$result = $res_55;
				$this->pos = $pos_55;
				$_128 = NULL;
				do {
					$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Identifier" );
					}
					else { $_128 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_128 = FALSE; break; }
					$res_127 = $result;
					$pos_127 = $this->pos;
					$_126 = NULL;
					do {
						$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
						$_107 = NULL;
						do {
							$_105 = NULL;
							do {
								$res_70 = $result;
								$pos_70 = $this->pos;
								if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_105 = TRUE; break;
								}
								$result = $res_70;
								$this->pos = $pos_70;
								$_103 = NULL;
								do {
									$res_72 = $result;
									$pos_72 = $this->pos;
									$matcher = 'match_'.'PrefixMatch'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_103 = TRUE; break;
									}
									$result = $res_72;
									$this->pos = $pos_72;
									$_101 = NULL;
									do {
										$res_74 = $result;
										$pos_74 = $this->pos;
										$matcher = 'match_'.'SuffixMatch'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_101 = TRUE; break;
										}
										$result = $res_74;
										$this->pos = $pos_74;
										$_99 = NULL;
										do {
											$res_76 = $result;
											$pos_76 = $this->pos;
											$matcher = 'match_'.'SubstringMatch'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_99 = TRUE; break;
											}
											$result = $res_76;
											$this->pos = $pos_76;
											$_97 = NULL;
											do {
												$res_78 = $result;
												$pos_78 = $this->pos;
												$matcher = 'match_'.'ExactMatch'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_97 = TRUE; break;
												}
												$result = $res_78;
												$this->pos = $pos_78;
												$_95 = NULL;
												do {
													$res_80 = $result;
													$pos_80 = $this->pos;
													$matcher = 'match_'.'NotEqualMatch'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_95 = TRUE; break;
													}
													$result = $res_80;
													$this->pos = $pos_80;
													$_93 = NULL;
													do {
														$res_82 = $result;
														$pos_82 = $this->pos;
														$matcher = 'match_'.'LessThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_93 = TRUE; break;
														}
														$result = $res_82;
														$this->pos = $pos_82;
														$_91 = NULL;
														do {
															$res_84 = $result;
															$pos_84 = $this->pos;
															$matcher = 'match_'.'LessThanMatch'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_91 = TRUE; break;
															}
															$result = $res_84;
															$this->pos = $pos_84;
															$_89 = NULL;
															do {
																$res_86 = $result;
																$pos_86 = $this->pos;
																$matcher = 'match_'.'GreaterThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_89 = TRUE; break;
																}
																$result = $res_86;
																$this->pos = $pos_86;
																$matcher = 'match_'.'GreaterThanMatch'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_89 = TRUE; break;
																}
																$result = $res_86;
																$this->pos = $pos_86;
																$_89 = FALSE; break;
															}
															while(0);
															if( $_89 === TRUE ) { $_91 = TRUE; break; }
															$result = $res_84;
															$this->pos = $pos_84;
															$_91 = FALSE; break;
														}
														while(0);
														if( $_91 === TRUE ) { $_93 = TRUE; break; }
														$result = $res_82;
														$this->pos = $pos_82;
														$_93 = FALSE; break;
													}
													while(0);
													if( $_93 === TRUE ) { $_95 = TRUE; break; }
													$result = $res_80;
													$this->pos = $pos_80;
													$_95 = FALSE; break;
												}
												while(0);
												if( $_95 === TRUE ) { $_97 = TRUE; break; }
												$result = $res_78;
												$this->pos = $pos_78;
												$_97 = FALSE; break;
											}
											while(0);
											if( $_97 === TRUE ) { $_99 = TRUE; break; }
											$result = $res_76;
											$this->pos = $pos_76;
											$_99 = FALSE; break;
										}
										while(0);
										if( $_99 === TRUE ) { $_101 = TRUE; break; }
										$result = $res_74;
										$this->pos = $pos_74;
										$_101 = FALSE; break;
									}
									while(0);
									if( $_101 === TRUE ) { $_103 = TRUE; break; }
									$result = $res_72;
									$this->pos = $pos_72;
									$_103 = FALSE; break;
								}
								while(0);
								if( $_103 === TRUE ) { $_105 = TRUE; break; }
								$result = $res_70;
								$this->pos = $pos_70;
								$_105 = FALSE; break;
							}
							while(0);
							if( $_105 === FALSE) { $_107 = FALSE; break; }
							$_107 = TRUE; break;
						}
						while(0);
						if( $_107 === TRUE ) {
							$subres = $result; $result = array_pop($stack);
							$this->store( $result, $subres, 'Operator' );
						}
						if( $_107 === FALSE) {
							$result = array_pop($stack);
							$_126 = FALSE; break;
						}
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_126 = FALSE; break; }
						$_123 = NULL;
						do {
							$_121 = NULL;
							do {
								$res_110 = $result;
								$pos_110 = $this->pos;
								$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres, "Operand" );
									$_121 = TRUE; break;
								}
								$result = $res_110;
								$this->pos = $pos_110;
								$_119 = NULL;
								do {
									$res_112 = $result;
									$pos_112 = $this->pos;
									$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres, "Operand" );
										$_119 = TRUE; break;
									}
									$result = $res_112;
									$this->pos = $pos_112;
									$_117 = NULL;
									do {
										$res_114 = $result;
										$pos_114 = $this->pos;
										$matcher = 'match_'.'BooleanLiteral'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_117 = TRUE; break;
										}
										$result = $res_114;
										$this->pos = $pos_114;
										$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_117 = TRUE; break;
										}
										$result = $res_114;
										$this->pos = $pos_114;
										$_117 = FALSE; break;
									}
									while(0);
									if( $_117 === TRUE ) { $_119 = TRUE; break; }
									$result = $res_112;
									$this->pos = $pos_112;
									$_119 = FALSE; break;
								}
								while(0);
								if( $_119 === TRUE ) { $_121 = TRUE; break; }
								$result = $res_110;
								$this->pos = $pos_110;
								$_121 = FALSE; break;
							}
							while(0);
							if( $_121 === FALSE) { $_123 = FALSE; break; }
							$_123 = TRUE; break;
						}
						while(0);
						if( $_123 === FALSE) { $_126 = FALSE; break; }
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_126 = FALSE; break; }
						$_126 = TRUE; break;
					}
					while(0);
					if( $_126 === FALSE) {
						$result = $res_127;
						$this->pos = $pos_127;
						unset( $res_127 );
						unset( $pos_127 );
					}
					$_128 = TRUE; break;
				}
				while(0);
				if( $_128 === TRUE ) { $_130 = TRUE; break; }
				$result = $res_55;
				$this->pos = $pos_55;
				$_130 = FALSE; break;
			}
			while(0);
			if( $_130 === FALSE) { $_132 = FALSE; break; }
			$_132 = TRUE; break;
		}
		while(0);
		if( $_132 === FALSE) { $_136 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, [$result])) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_136 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_136 = FALSE; break; }
		$_136 = TRUE; break;
	}
	while(0);
	if( $_136 === TRUE ) { return $this->finalise($result); }
	if( $_136 === FALSE) { return FALSE; }
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
protected $match_UnquotedOperand_typestack = ['UnquotedOperand'];
function match_UnquotedOperand ($stack = []) {
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
protected $match_PrefixMatch_typestack = ['PrefixMatch'];
function match_PrefixMatch ($stack = []) {
	$matchrule = "PrefixMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '^=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* SuffixMatch: '$=' */
protected $match_SuffixMatch_typestack = ['SuffixMatch'];
function match_SuffixMatch ($stack = []) {
	$matchrule = "SuffixMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '$=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* SubstringMatch: '*=' */
protected $match_SubstringMatch_typestack = ['SubstringMatch'];
function match_SubstringMatch ($stack = []) {
	$matchrule = "SubstringMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '*=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* ExactMatch: '=' */
protected $match_ExactMatch_typestack = ['ExactMatch'];
function match_ExactMatch ($stack = []) {
	$matchrule = "ExactMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (substr($this->string,$this->pos,1) == '=') {
		$this->pos += 1;
		$result["text"] .= '=';
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* NotEqualMatch: '!=' */
protected $match_NotEqualMatch_typestack = ['NotEqualMatch'];
function match_NotEqualMatch ($stack = []) {
	$matchrule = "NotEqualMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '!=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* LessThanOrEqualMatch: '<=' */
protected $match_LessThanOrEqualMatch_typestack = ['LessThanOrEqualMatch'];
function match_LessThanOrEqualMatch ($stack = []) {
	$matchrule = "LessThanOrEqualMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '<=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* LessThanMatch: '<' */
protected $match_LessThanMatch_typestack = ['LessThanMatch'];
function match_LessThanMatch ($stack = []) {
	$matchrule = "LessThanMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (substr($this->string,$this->pos,1) == '<') {
		$this->pos += 1;
		$result["text"] .= '<';
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* GreaterThanOrEqualMatch: '>=' */
protected $match_GreaterThanOrEqualMatch_typestack = ['GreaterThanOrEqualMatch'];
function match_GreaterThanOrEqualMatch ($stack = []) {
	$matchrule = "GreaterThanOrEqualMatch"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->literal( '>=' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* GreaterThanMatch: '>' */
protected $match_GreaterThanMatch_typestack = ['GreaterThanMatch'];
function match_GreaterThanMatch ($stack = []) {
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
?>
