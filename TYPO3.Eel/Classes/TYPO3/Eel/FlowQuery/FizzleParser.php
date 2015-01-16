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

/* Filter: ( :IdentifierFilter ) ? ( :PropertyNameFilter ) ?  ( AttributeFilters:AttributeFilter )* */
protected $match_Filter_typestack = array('Filter');
function match_Filter ($stack = array()) {
	$matchrule = "Filter"; $result = $this->construct($matchrule, $matchrule, null);
	$_19 = NULL;
	do {
		$res_12 = $result;
		$pos_12 = $this->pos;
		$_11 = NULL;
		do {
			$matcher = 'match_'.'IdentifierFilter'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "IdentifierFilter" );
			}
			else { $_11 = FALSE; break; }
			$_11 = TRUE; break;
		}
		while(0);
		if( $_11 === FALSE) {
			$result = $res_12;
			$this->pos = $pos_12;
			unset( $res_12 );
			unset( $pos_12 );
		}
		$res_15 = $result;
		$pos_15 = $this->pos;
		$_14 = NULL;
		do {
			$matcher = 'match_'.'PropertyNameFilter'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "PropertyNameFilter" );
			}
			else { $_14 = FALSE; break; }
			$_14 = TRUE; break;
		}
		while(0);
		if( $_14 === FALSE) {
			$result = $res_15;
			$this->pos = $pos_15;
			unset( $res_15 );
			unset( $pos_15 );
		}
		while (true) {
			$res_18 = $result;
			$pos_18 = $this->pos;
			$_17 = NULL;
			do {
				$matcher = 'match_'.'AttributeFilter'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "AttributeFilters" );
				}
				else { $_17 = FALSE; break; }
				$_17 = TRUE; break;
			}
			while(0);
			if( $_17 === FALSE) {
				$result = $res_18;
				$this->pos = $pos_18;
				unset( $res_18 );
				unset( $pos_18 );
				break;
			}
		}
		$_19 = TRUE; break;
	}
	while(0);
	if( $_19 === TRUE ) { return $this->finalise($result); }
	if( $_19 === FALSE) { return FALSE; }
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

function Filter_IdentifierFilter (&$result, $sub) {
		$result['IdentifierFilter'] = substr($sub['text'], 1);
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

/* IdentifierFilter: '#':ObjectIdentifier */
protected $match_IdentifierFilter_typestack = array('IdentifierFilter');
function match_IdentifierFilter ($stack = array()) {
	$matchrule = "IdentifierFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_24 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '#') {
			$this->pos += 1;
			$result["text"] .= '#';
		}
		else { $_24 = FALSE; break; }
		$matcher = 'match_'.'ObjectIdentifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "ObjectIdentifier" );
		}
		else { $_24 = FALSE; break; }
		$_24 = TRUE; break;
	}
	while(0);
	if( $_24 === TRUE ) { return $this->finalise($result); }
	if( $_24 === FALSE) { return FALSE; }
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
protected $match_AttributeFilter_typestack = array('AttributeFilter');
function match_AttributeFilter ($stack = array()) {
	$matchrule = "AttributeFilter"; $result = $this->construct($matchrule, $matchrule, null);
	$_109 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_109 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_109 = FALSE; break; }
		$_105 = NULL;
		do {
			$_103 = NULL;
			do {
				$res_28 = $result;
				$pos_28 = $this->pos;
				$_39 = NULL;
				do {
					$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
					if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
						$result["text"] .= $subres;
						$subres = $result; $result = array_pop($stack);
						$this->store( $result, $subres, 'Operator' );
					}
					else {
						$result = array_pop($stack);
						$_39 = FALSE; break;
					}
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_39 = FALSE; break; }
					$_36 = NULL;
					do {
						$_34 = NULL;
						do {
							$res_31 = $result;
							$pos_31 = $this->pos;
							$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_34 = TRUE; break;
							}
							$result = $res_31;
							$this->pos = $pos_31;
							$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
							$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
							if ($subres !== FALSE) {
								$this->store( $result, $subres, "Operand" );
								$_34 = TRUE; break;
							}
							$result = $res_31;
							$this->pos = $pos_31;
							$_34 = FALSE; break;
						}
						while(0);
						if( $_34 === FALSE) { $_36 = FALSE; break; }
						$_36 = TRUE; break;
					}
					while(0);
					if( $_36 === FALSE) { $_39 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_39 = FALSE; break; }
					$_39 = TRUE; break;
				}
				while(0);
				if( $_39 === TRUE ) { $_103 = TRUE; break; }
				$result = $res_28;
				$this->pos = $pos_28;
				$_101 = NULL;
				do {
					$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "Identifier" );
					}
					else { $_101 = FALSE; break; }
					$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) { $this->store( $result, $subres ); }
					else { $_101 = FALSE; break; }
					$res_100 = $result;
					$pos_100 = $this->pos;
					$_99 = NULL;
					do {
						$stack[] = $result; $result = $this->construct( $matchrule, "Operator" );
						$_80 = NULL;
						do {
							$_78 = NULL;
							do {
								$res_43 = $result;
								$pos_43 = $this->pos;
								if (( $subres = $this->literal( 'instanceof' ) ) !== FALSE) {
									$result["text"] .= $subres;
									$_78 = TRUE; break;
								}
								$result = $res_43;
								$this->pos = $pos_43;
								$_76 = NULL;
								do {
									$res_45 = $result;
									$pos_45 = $this->pos;
									$matcher = 'match_'.'PrefixMatch'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres );
										$_76 = TRUE; break;
									}
									$result = $res_45;
									$this->pos = $pos_45;
									$_74 = NULL;
									do {
										$res_47 = $result;
										$pos_47 = $this->pos;
										$matcher = 'match_'.'SuffixMatch'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres );
											$_74 = TRUE; break;
										}
										$result = $res_47;
										$this->pos = $pos_47;
										$_72 = NULL;
										do {
											$res_49 = $result;
											$pos_49 = $this->pos;
											$matcher = 'match_'.'SubstringMatch'; $key = $matcher; $pos = $this->pos;
											$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
											if ($subres !== FALSE) {
												$this->store( $result, $subres );
												$_72 = TRUE; break;
											}
											$result = $res_49;
											$this->pos = $pos_49;
											$_70 = NULL;
											do {
												$res_51 = $result;
												$pos_51 = $this->pos;
												$matcher = 'match_'.'ExactMatch'; $key = $matcher; $pos = $this->pos;
												$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
												if ($subres !== FALSE) {
													$this->store( $result, $subres );
													$_70 = TRUE; break;
												}
												$result = $res_51;
												$this->pos = $pos_51;
												$_68 = NULL;
												do {
													$res_53 = $result;
													$pos_53 = $this->pos;
													$matcher = 'match_'.'NotEqualMatch'; $key = $matcher; $pos = $this->pos;
													$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
													if ($subres !== FALSE) {
														$this->store( $result, $subres );
														$_68 = TRUE; break;
													}
													$result = $res_53;
													$this->pos = $pos_53;
													$_66 = NULL;
													do {
														$res_55 = $result;
														$pos_55 = $this->pos;
														$matcher = 'match_'.'LessThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
														$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
														if ($subres !== FALSE) {
															$this->store( $result, $subres );
															$_66 = TRUE; break;
														}
														$result = $res_55;
														$this->pos = $pos_55;
														$_64 = NULL;
														do {
															$res_57 = $result;
															$pos_57 = $this->pos;
															$matcher = 'match_'.'LessThanMatch'; $key = $matcher; $pos = $this->pos;
															$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
															if ($subres !== FALSE) {
																$this->store( $result, $subres );
																$_64 = TRUE; break;
															}
															$result = $res_57;
															$this->pos = $pos_57;
															$_62 = NULL;
															do {
																$res_59 = $result;
																$pos_59 = $this->pos;
																$matcher = 'match_'.'GreaterThanOrEqualMatch'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_62 = TRUE; break;
																}
																$result = $res_59;
																$this->pos = $pos_59;
																$matcher = 'match_'.'GreaterThanMatch'; $key = $matcher; $pos = $this->pos;
																$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
																if ($subres !== FALSE) {
																	$this->store( $result, $subres );
																	$_62 = TRUE; break;
																}
																$result = $res_59;
																$this->pos = $pos_59;
																$_62 = FALSE; break;
															}
															while(0);
															if( $_62 === TRUE ) { $_64 = TRUE; break; }
															$result = $res_57;
															$this->pos = $pos_57;
															$_64 = FALSE; break;
														}
														while(0);
														if( $_64 === TRUE ) { $_66 = TRUE; break; }
														$result = $res_55;
														$this->pos = $pos_55;
														$_66 = FALSE; break;
													}
													while(0);
													if( $_66 === TRUE ) { $_68 = TRUE; break; }
													$result = $res_53;
													$this->pos = $pos_53;
													$_68 = FALSE; break;
												}
												while(0);
												if( $_68 === TRUE ) { $_70 = TRUE; break; }
												$result = $res_51;
												$this->pos = $pos_51;
												$_70 = FALSE; break;
											}
											while(0);
											if( $_70 === TRUE ) { $_72 = TRUE; break; }
											$result = $res_49;
											$this->pos = $pos_49;
											$_72 = FALSE; break;
										}
										while(0);
										if( $_72 === TRUE ) { $_74 = TRUE; break; }
										$result = $res_47;
										$this->pos = $pos_47;
										$_74 = FALSE; break;
									}
									while(0);
									if( $_74 === TRUE ) { $_76 = TRUE; break; }
									$result = $res_45;
									$this->pos = $pos_45;
									$_76 = FALSE; break;
								}
								while(0);
								if( $_76 === TRUE ) { $_78 = TRUE; break; }
								$result = $res_43;
								$this->pos = $pos_43;
								$_78 = FALSE; break;
							}
							while(0);
							if( $_78 === FALSE) { $_80 = FALSE; break; }
							$_80 = TRUE; break;
						}
						while(0);
						if( $_80 === TRUE ) {
							$subres = $result; $result = array_pop($stack);
							$this->store( $result, $subres, 'Operator' );
						}
						if( $_80 === FALSE) {
							$result = array_pop($stack);
							$_99 = FALSE; break;
						}
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_99 = FALSE; break; }
						$_96 = NULL;
						do {
							$_94 = NULL;
							do {
								$res_83 = $result;
								$pos_83 = $this->pos;
								$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres, "Operand" );
									$_94 = TRUE; break;
								}
								$result = $res_83;
								$this->pos = $pos_83;
								$_92 = NULL;
								do {
									$res_85 = $result;
									$pos_85 = $this->pos;
									$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
									$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
									if ($subres !== FALSE) {
										$this->store( $result, $subres, "Operand" );
										$_92 = TRUE; break;
									}
									$result = $res_85;
									$this->pos = $pos_85;
									$_90 = NULL;
									do {
										$res_87 = $result;
										$pos_87 = $this->pos;
										$matcher = 'match_'.'BooleanLiteral'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_90 = TRUE; break;
										}
										$result = $res_87;
										$this->pos = $pos_87;
										$matcher = 'match_'.'UnquotedOperand'; $key = $matcher; $pos = $this->pos;
										$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
										if ($subres !== FALSE) {
											$this->store( $result, $subres, "Operand" );
											$_90 = TRUE; break;
										}
										$result = $res_87;
										$this->pos = $pos_87;
										$_90 = FALSE; break;
									}
									while(0);
									if( $_90 === TRUE ) { $_92 = TRUE; break; }
									$result = $res_85;
									$this->pos = $pos_85;
									$_92 = FALSE; break;
								}
								while(0);
								if( $_92 === TRUE ) { $_94 = TRUE; break; }
								$result = $res_83;
								$this->pos = $pos_83;
								$_94 = FALSE; break;
							}
							while(0);
							if( $_94 === FALSE) { $_96 = FALSE; break; }
							$_96 = TRUE; break;
						}
						while(0);
						if( $_96 === FALSE) { $_99 = FALSE; break; }
						$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres );
						}
						else { $_99 = FALSE; break; }
						$_99 = TRUE; break;
					}
					while(0);
					if( $_99 === FALSE) {
						$result = $res_100;
						$this->pos = $pos_100;
						unset( $res_100 );
						unset( $pos_100 );
					}
					$_101 = TRUE; break;
				}
				while(0);
				if( $_101 === TRUE ) { $_103 = TRUE; break; }
				$result = $res_28;
				$this->pos = $pos_28;
				$_103 = FALSE; break;
			}
			while(0);
			if( $_103 === FALSE) { $_105 = FALSE; break; }
			$_105 = TRUE; break;
		}
		while(0);
		if( $_105 === FALSE) { $_109 = FALSE; break; }
		$matcher = 'match_'.'S'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_109 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_109 = FALSE; break; }
		$_109 = TRUE; break;
	}
	while(0);
	if( $_109 === TRUE ) { return $this->finalise($result); }
	if( $_109 === FALSE) { return FALSE; }
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
?>