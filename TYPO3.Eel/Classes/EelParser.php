<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/


/**
 * Eel parser
 *
 * This parser can evaluate the expression language for FLOW3 and uses the
 * basic types from AbstractParser.
 */
class EelParser extends \TYPO3\Eel\AbstractParser {

/* OffsetAccess: '[' < Expression > ']' */
protected $match_OffsetAccess_typestack = array('OffsetAccess');
function match_OffsetAccess ($stack = array()) {
	$matchrule = "OffsetAccess"; $result = $this->construct($matchrule, $matchrule, null);
	$_5 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_5 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_5 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_5 = FALSE; break; }
		$_5 = TRUE; break;
	}
	while(0);
	if( $_5 === TRUE ) { return $this->finalise($result); }
	if( $_5 === FALSE) { return FALSE; }
}


/* MethodCall: Identifier '(' < Expression? > (',' < Expression > )* ')' */
protected $match_MethodCall_typestack = array('MethodCall');
function match_MethodCall ($stack = array()) {
	$matchrule = "MethodCall"; $result = $this->construct($matchrule, $matchrule, null);
	$_19 = NULL;
	do {
		$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_19 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '(') {
			$this->pos += 1;
			$result["text"] .= '(';
		}
		else { $_19 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$res_10 = $result;
		$pos_10 = $this->pos;
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else {
			$result = $res_10;
			$this->pos = $pos_10;
			unset( $res_10 );
			unset( $pos_10 );
		}
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		while (true) {
			$res_17 = $result;
			$pos_17 = $this->pos;
			$_16 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_16 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_16 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$_16 = TRUE; break;
			}
			while(0);
			if( $_16 === FALSE) {
				$result = $res_17;
				$this->pos = $pos_17;
				unset( $res_17 );
				unset( $pos_17 );
				break;
			}
		}
		if (substr($this->string,$this->pos,1) == ')') {
			$this->pos += 1;
			$result["text"] .= ')';
		}
		else { $_19 = FALSE; break; }
		$_19 = TRUE; break;
	}
	while(0);
	if( $_19 === TRUE ) { return $this->finalise($result); }
	if( $_19 === FALSE) { return FALSE; }
}


/* ObjectPath: (MethodCall | Identifier) ('.' (MethodCall | Identifier) | OffsetAccess)* */
protected $match_ObjectPath_typestack = array('ObjectPath');
function match_ObjectPath ($stack = array()) {
	$matchrule = "ObjectPath"; $result = $this->construct($matchrule, $matchrule, null);
	$_44 = NULL;
	do {
		$_26 = NULL;
		do {
			$_24 = NULL;
			do {
				$res_21 = $result;
				$pos_21 = $this->pos;
				$matcher = 'match_'.'MethodCall'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres );
					$_24 = TRUE; break;
				}
				$result = $res_21;
				$this->pos = $pos_21;
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres );
					$_24 = TRUE; break;
				}
				$result = $res_21;
				$this->pos = $pos_21;
				$_24 = FALSE; break;
			}
			while(0);
			if( $_24 === FALSE) { $_26 = FALSE; break; }
			$_26 = TRUE; break;
		}
		while(0);
		if( $_26 === FALSE) { $_44 = FALSE; break; }
		while (true) {
			$res_43 = $result;
			$pos_43 = $this->pos;
			$_42 = NULL;
			do {
				$_40 = NULL;
				do {
					$res_28 = $result;
					$pos_28 = $this->pos;
					$_37 = NULL;
					do {
						if (substr($this->string,$this->pos,1) == '.') {
							$this->pos += 1;
							$result["text"] .= '.';
						}
						else { $_37 = FALSE; break; }
						$_35 = NULL;
						do {
							$_33 = NULL;
							do {
								$res_30 = $result;
								$pos_30 = $this->pos;
								$matcher = 'match_'.'MethodCall'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_33 = TRUE; break;
								}
								$result = $res_30;
								$this->pos = $pos_30;
								$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== FALSE) {
									$this->store( $result, $subres );
									$_33 = TRUE; break;
								}
								$result = $res_30;
								$this->pos = $pos_30;
								$_33 = FALSE; break;
							}
							while(0);
							if( $_33 === FALSE) { $_35 = FALSE; break; }
							$_35 = TRUE; break;
						}
						while(0);
						if( $_35 === FALSE) { $_37 = FALSE; break; }
						$_37 = TRUE; break;
					}
					while(0);
					if( $_37 === TRUE ) { $_40 = TRUE; break; }
					$result = $res_28;
					$this->pos = $pos_28;
					$matcher = 'match_'.'OffsetAccess'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres );
						$_40 = TRUE; break;
					}
					$result = $res_28;
					$this->pos = $pos_28;
					$_40 = FALSE; break;
				}
				while(0);
				if( $_40 === FALSE) { $_42 = FALSE; break; }
				$_42 = TRUE; break;
			}
			while(0);
			if( $_42 === FALSE) {
				$result = $res_43;
				$this->pos = $pos_43;
				unset( $res_43 );
				unset( $pos_43 );
				break;
			}
		}
		$_44 = TRUE; break;
	}
	while(0);
	if( $_44 === TRUE ) { return $this->finalise($result); }
	if( $_44 === FALSE) { return FALSE; }
}


/* Term: term:BooleanLiteral !Identifier | term:NumberLiteral | term:StringLiteral | term:ObjectPath */
protected $match_Term_typestack = array('Term');
function match_Term ($stack = array()) {
	$matchrule = "Term"; $result = $this->construct($matchrule, $matchrule, null);
	$_60 = NULL;
	do {
		$res_46 = $result;
		$pos_46 = $this->pos;
		$_49 = NULL;
		do {
			$matcher = 'match_'.'BooleanLiteral'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "term" );
			}
			else { $_49 = FALSE; break; }
			$res_48 = $result;
			$pos_48 = $this->pos;
			$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres );
				$result = $res_48;
				$this->pos = $pos_48;
				$_49 = FALSE; break;
			}
			else {
				$result = $res_48;
				$this->pos = $pos_48;
			}
			$_49 = TRUE; break;
		}
		while(0);
		if( $_49 === TRUE ) { $_60 = TRUE; break; }
		$result = $res_46;
		$this->pos = $pos_46;
		$_58 = NULL;
		do {
			$res_51 = $result;
			$pos_51 = $this->pos;
			$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "term" );
				$_58 = TRUE; break;
			}
			$result = $res_51;
			$this->pos = $pos_51;
			$_56 = NULL;
			do {
				$res_53 = $result;
				$pos_53 = $this->pos;
				$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "term" );
					$_56 = TRUE; break;
				}
				$result = $res_53;
				$this->pos = $pos_53;
				$matcher = 'match_'.'ObjectPath'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "term" );
					$_56 = TRUE; break;
				}
				$result = $res_53;
				$this->pos = $pos_53;
				$_56 = FALSE; break;
			}
			while(0);
			if( $_56 === TRUE ) { $_58 = TRUE; break; }
			$result = $res_51;
			$this->pos = $pos_51;
			$_58 = FALSE; break;
		}
		while(0);
		if( $_58 === TRUE ) { $_60 = TRUE; break; }
		$result = $res_46;
		$this->pos = $pos_46;
		$_60 = FALSE; break;
	}
	while(0);
	if( $_60 === TRUE ) { return $this->finalise($result); }
	if( $_60 === FALSE) { return FALSE; }
}




/* Expression: exp:ConditionalExpression */
protected $match_Expression_typestack = array('Expression');
function match_Expression ($stack = array()) {
	$matchrule = "Expression"; $result = $this->construct($matchrule, $matchrule, null);
	$matcher = 'match_'.'ConditionalExpression'; $key = $matcher; $pos = $this->pos;
	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
	if ($subres !== FALSE) {
		$this->store( $result, $subres, "exp" );
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* SimpleExpression: term:WrappedExpression | term:NotExpression | term:ArrayLiteral | term:Term */
protected $match_SimpleExpression_typestack = array('SimpleExpression');
function match_SimpleExpression ($stack = array()) {
	$matchrule = "SimpleExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_74 = NULL;
	do {
		$res_63 = $result;
		$pos_63 = $this->pos;
		$matcher = 'match_'.'WrappedExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "term" );
			$_74 = TRUE; break;
		}
		$result = $res_63;
		$this->pos = $pos_63;
		$_72 = NULL;
		do {
			$res_65 = $result;
			$pos_65 = $this->pos;
			$matcher = 'match_'.'NotExpression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "term" );
				$_72 = TRUE; break;
			}
			$result = $res_65;
			$this->pos = $pos_65;
			$_70 = NULL;
			do {
				$res_67 = $result;
				$pos_67 = $this->pos;
				$matcher = 'match_'.'ArrayLiteral'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "term" );
					$_70 = TRUE; break;
				}
				$result = $res_67;
				$this->pos = $pos_67;
				$matcher = 'match_'.'Term'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "term" );
					$_70 = TRUE; break;
				}
				$result = $res_67;
				$this->pos = $pos_67;
				$_70 = FALSE; break;
			}
			while(0);
			if( $_70 === TRUE ) { $_72 = TRUE; break; }
			$result = $res_65;
			$this->pos = $pos_65;
			$_72 = FALSE; break;
		}
		while(0);
		if( $_72 === TRUE ) { $_74 = TRUE; break; }
		$result = $res_63;
		$this->pos = $pos_63;
		$_74 = FALSE; break;
	}
	while(0);
	if( $_74 === TRUE ) { return $this->finalise($result); }
	if( $_74 === FALSE) { return FALSE; }
}


/* WrappedExpression: '(' < Expression > ')' */
protected $match_WrappedExpression_typestack = array('WrappedExpression');
function match_WrappedExpression ($stack = array()) {
	$matchrule = "WrappedExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_81 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '(') {
			$this->pos += 1;
			$result["text"] .= '(';
		}
		else { $_81 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_81 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ')') {
			$this->pos += 1;
			$result["text"] .= ')';
		}
		else { $_81 = FALSE; break; }
		$_81 = TRUE; break;
	}
	while(0);
	if( $_81 === TRUE ) { return $this->finalise($result); }
	if( $_81 === FALSE) { return FALSE; }
}


/* NotExpression: (/ ! | not\s+ /) > exp:SimpleExpression */
protected $match_NotExpression_typestack = array('NotExpression');
function match_NotExpression ($stack = array()) {
	$matchrule = "NotExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_88 = NULL;
	do {
		$_84 = NULL;
		do {
			if (( $subres = $this->rx( '/ ! | not\s+ /' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_84 = FALSE; break; }
			$_84 = TRUE; break;
		}
		while(0);
		if( $_84 === FALSE) { $_88 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "exp" );
		}
		else { $_88 = FALSE; break; }
		$_88 = TRUE; break;
	}
	while(0);
	if( $_88 === TRUE ) { return $this->finalise($result); }
	if( $_88 === FALSE) { return FALSE; }
}


/* ConditionalExpression: cond:Disjunction (< '?' > then:Expression < ':' > else:Expression)? */
protected $match_ConditionalExpression_typestack = array('ConditionalExpression');
function match_ConditionalExpression ($stack = array()) {
	$matchrule = "ConditionalExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_101 = NULL;
	do {
		$matcher = 'match_'.'Disjunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "cond" );
		}
		else { $_101 = FALSE; break; }
		$res_100 = $result;
		$pos_100 = $this->pos;
		$_99 = NULL;
		do {
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '?') {
				$this->pos += 1;
				$result["text"] .= '?';
			}
			else { $_99 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "then" );
			}
			else { $_99 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_99 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "else" );
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
	if( $_101 === TRUE ) { return $this->finalise($result); }
	if( $_101 === FALSE) { return FALSE; }
}


/* Disjunction: lft:Conjunction (< / \|\| | or\s+ / > rgt:Conjunction)* */
protected $match_Disjunction_typestack = array('Disjunction');
function match_Disjunction ($stack = array()) {
	$matchrule = "Disjunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_110 = NULL;
	do {
		$matcher = 'match_'.'Conjunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_110 = FALSE; break; }
		while (true) {
			$res_109 = $result;
			$pos_109 = $this->pos;
			$_108 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->rx( '/ \|\| | or\s+ /' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_108 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Conjunction'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_108 = FALSE; break; }
				$_108 = TRUE; break;
			}
			while(0);
			if( $_108 === FALSE) {
				$result = $res_109;
				$this->pos = $pos_109;
				unset( $res_109 );
				unset( $pos_109 );
				break;
			}
		}
		$_110 = TRUE; break;
	}
	while(0);
	if( $_110 === TRUE ) { return $this->finalise($result); }
	if( $_110 === FALSE) { return FALSE; }
}


/* Conjunction: lft:Comparison (< / && | and\s+ / > rgt:Comparison)* */
protected $match_Conjunction_typestack = array('Conjunction');
function match_Conjunction ($stack = array()) {
	$matchrule = "Conjunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_119 = NULL;
	do {
		$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_119 = FALSE; break; }
		while (true) {
			$res_118 = $result;
			$pos_118 = $this->pos;
			$_117 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->rx( '/ && | and\s+ /' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_117 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_117 = FALSE; break; }
				$_117 = TRUE; break;
			}
			while(0);
			if( $_117 === FALSE) {
				$result = $res_118;
				$this->pos = $pos_118;
				unset( $res_118 );
				unset( $pos_118 );
				break;
			}
		}
		$_119 = TRUE; break;
	}
	while(0);
	if( $_119 === TRUE ) { return $this->finalise($result); }
	if( $_119 === FALSE) { return FALSE; }
}


/* Comparison: lft:SumCalculation (< comp:/ == | <= | >= | < | > / > rgt:SumCalculation)? */
protected $match_Comparison_typestack = array('Comparison');
function match_Comparison ($stack = array()) {
	$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
	$_128 = NULL;
	do {
		$matcher = 'match_'.'SumCalculation'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_128 = FALSE; break; }
		$res_127 = $result;
		$pos_127 = $this->pos;
		$_126 = NULL;
		do {
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "comp" ); 
			if (( $subres = $this->rx( '/ == | <= | >= | < | > /' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'comp' );
			}
			else {
				$result = array_pop($stack);
				$_126 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'SumCalculation'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "rgt" );
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
	if( $_128 === TRUE ) { return $this->finalise($result); }
	if( $_128 === FALSE) { return FALSE; }
}


/* SumCalculation: lft:ProdCalculation (< op:/ \+ | \- / > rgt:ProdCalculation)* */
protected $match_SumCalculation_typestack = array('SumCalculation');
function match_SumCalculation ($stack = array()) {
	$matchrule = "SumCalculation"; $result = $this->construct($matchrule, $matchrule, null);
	$_137 = NULL;
	do {
		$matcher = 'match_'.'ProdCalculation'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_137 = FALSE; break; }
		while (true) {
			$res_136 = $result;
			$pos_136 = $this->pos;
			$_135 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "op" ); 
				if (( $subres = $this->rx( '/ \+ | \- /' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'op' );
				}
				else {
					$result = array_pop($stack);
					$_135 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'ProdCalculation'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_135 = FALSE; break; }
				$_135 = TRUE; break;
			}
			while(0);
			if( $_135 === FALSE) {
				$result = $res_136;
				$this->pos = $pos_136;
				unset( $res_136 );
				unset( $pos_136 );
				break;
			}
		}
		$_137 = TRUE; break;
	}
	while(0);
	if( $_137 === TRUE ) { return $this->finalise($result); }
	if( $_137 === FALSE) { return FALSE; }
}


/* ProdCalculation: lft:SimpleExpression (< op:/ \/ | \* | % / > rgt:SimpleExpression)* */
protected $match_ProdCalculation_typestack = array('ProdCalculation');
function match_ProdCalculation ($stack = array()) {
	$matchrule = "ProdCalculation"; $result = $this->construct($matchrule, $matchrule, null);
	$_146 = NULL;
	do {
		$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_146 = FALSE; break; }
		while (true) {
			$res_145 = $result;
			$pos_145 = $this->pos;
			$_144 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "op" ); 
				if (( $subres = $this->rx( '/ \/ | \* | % /' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'op' );
				}
				else {
					$result = array_pop($stack);
					$_144 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_144 = FALSE; break; }
				$_144 = TRUE; break;
			}
			while(0);
			if( $_144 === FALSE) {
				$result = $res_145;
				$this->pos = $pos_145;
				unset( $res_145 );
				unset( $pos_145 );
				break;
			}
		}
		$_146 = TRUE; break;
	}
	while(0);
	if( $_146 === TRUE ) { return $this->finalise($result); }
	if( $_146 === FALSE) { return FALSE; }
}


/* ArrayLiteral: '[' < Expression? (< ',' > Expression)* > ']' */
protected $match_ArrayLiteral_typestack = array('ArrayLiteral');
function match_ArrayLiteral ($stack = array()) {
	$matchrule = "ArrayLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_159 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_159 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$res_150 = $result;
		$pos_150 = $this->pos;
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else {
			$result = $res_150;
			$this->pos = $pos_150;
			unset( $res_150 );
			unset( $pos_150 );
		}
		while (true) {
			$res_156 = $result;
			$pos_156 = $this->pos;
			$_155 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_155 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_155 = FALSE; break; }
				$_155 = TRUE; break;
			}
			while(0);
			if( $_155 === FALSE) {
				$result = $res_156;
				$this->pos = $pos_156;
				unset( $res_156 );
				unset( $pos_156 );
				break;
			}
		}
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_159 = FALSE; break; }
		$_159 = TRUE; break;
	}
	while(0);
	if( $_159 === TRUE ) { return $this->finalise($result); }
	if( $_159 === FALSE) { return FALSE; }
}




}
?>