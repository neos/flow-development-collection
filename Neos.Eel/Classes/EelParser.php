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

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/


/**
 * Eel parser
 *
 * This parser can evaluate the expression language for Flow and uses
 * the basic types from AbstractParser.
 *
 * @Neos\Flow\Annotations\Proxy(false)
 */
class EelParser extends \Neos\Eel\AbstractParser {

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
		else { $_5 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_5 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_5 = false; break; }
		$_5 = true; break;
	}
	while(0);
	if( $_5 === true ) { return $this->finalise($result); }
	if( $_5 === false) { return false; }
}


/* MethodCall: Identifier '(' < Expression? > (',' < Expression > )* ')' */
protected $match_MethodCall_typestack = array('MethodCall');
function match_MethodCall ($stack = array()) {
	$matchrule = "MethodCall"; $result = $this->construct($matchrule, $matchrule, null);
	$_19 = NULL;
	do {
		$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_19 = false; break; }
		if (substr($this->string,$this->pos,1) == '(') {
			$this->pos += 1;
			$result["text"] .= '(';
		}
		else { $_19 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$res_10 = $result;
		$pos_10 = $this->pos;
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else {
			$result = $res_10;
			$this->pos = $pos_10;
			unset( $res_10 );
			unset( $pos_10 );
		}
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		while (true) {
			$res_17 = $result;
			$pos_17 = $this->pos;
			$_16 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_16 = false; break; }
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_16 = false; break; }
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$_16 = true; break;
			}
			while(0);
			if( $_16 === false) {
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
		else { $_19 = false; break; }
		$_19 = true; break;
	}
	while(0);
	if( $_19 === true ) { return $this->finalise($result); }
	if( $_19 === false) { return false; }
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
				if ($subres !== false) {
					$this->store( $result, $subres );
					$_24 = true; break;
				}
				$result = $res_21;
				$this->pos = $pos_21;
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres );
					$_24 = true; break;
				}
				$result = $res_21;
				$this->pos = $pos_21;
				$_24 = false; break;
			}
			while(0);
			if( $_24 === false) { $_26 = false; break; }
			$_26 = true; break;
		}
		while(0);
		if( $_26 === false) { $_44 = false; break; }
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
						else { $_37 = false; break; }
						$_35 = NULL;
						do {
							$_33 = NULL;
							do {
								$res_30 = $result;
								$pos_30 = $this->pos;
								$matcher = 'match_'.'MethodCall'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== false) {
									$this->store( $result, $subres );
									$_33 = true; break;
								}
								$result = $res_30;
								$this->pos = $pos_30;
								$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
								$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
								if ($subres !== false) {
									$this->store( $result, $subres );
									$_33 = true; break;
								}
								$result = $res_30;
								$this->pos = $pos_30;
								$_33 = false; break;
							}
							while(0);
							if( $_33 === false) { $_35 = false; break; }
							$_35 = true; break;
						}
						while(0);
						if( $_35 === false) { $_37 = false; break; }
						$_37 = true; break;
					}
					while(0);
					if( $_37 === true ) { $_40 = true; break; }
					$result = $res_28;
					$this->pos = $pos_28;
					$matcher = 'match_'.'OffsetAccess'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== false) {
						$this->store( $result, $subres );
						$_40 = true; break;
					}
					$result = $res_28;
					$this->pos = $pos_28;
					$_40 = false; break;
				}
				while(0);
				if( $_40 === false) { $_42 = false; break; }
				$_42 = true; break;
			}
			while(0);
			if( $_42 === false) {
				$result = $res_43;
				$this->pos = $pos_43;
				unset( $res_43 );
				unset( $pos_43 );
				break;
			}
		}
		$_44 = true; break;
	}
	while(0);
	if( $_44 === true ) { return $this->finalise($result); }
	if( $_44 === false) { return false; }
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
			if ($subres !== false) {
				$this->store( $result, $subres, "term" );
			}
			else { $_49 = false; break; }
			$res_48 = $result;
			$pos_48 = $this->pos;
			$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== false) {
				$this->store( $result, $subres );
				$result = $res_48;
				$this->pos = $pos_48;
				$_49 = false; break;
			}
			else {
				$result = $res_48;
				$this->pos = $pos_48;
			}
			$_49 = true; break;
		}
		while(0);
		if( $_49 === true ) { $_60 = true; break; }
		$result = $res_46;
		$this->pos = $pos_46;
		$_58 = NULL;
		do {
			$res_51 = $result;
			$pos_51 = $this->pos;
			$matcher = 'match_'.'NumberLiteral'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== false) {
				$this->store( $result, $subres, "term" );
				$_58 = true; break;
			}
			$result = $res_51;
			$this->pos = $pos_51;
			$_56 = NULL;
			do {
				$res_53 = $result;
				$pos_53 = $this->pos;
				$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "term" );
					$_56 = true; break;
				}
				$result = $res_53;
				$this->pos = $pos_53;
				$matcher = 'match_'.'ObjectPath'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "term" );
					$_56 = true; break;
				}
				$result = $res_53;
				$this->pos = $pos_53;
				$_56 = false; break;
			}
			while(0);
			if( $_56 === true ) { $_58 = true; break; }
			$result = $res_51;
			$this->pos = $pos_51;
			$_58 = false; break;
		}
		while(0);
		if( $_58 === true ) { $_60 = true; break; }
		$result = $res_46;
		$this->pos = $pos_46;
		$_60 = false; break;
	}
	while(0);
	if( $_60 === true ) { return $this->finalise($result); }
	if( $_60 === false) { return false; }
}




/* Expression: exp:ConditionalExpression */
protected $match_Expression_typestack = array('Expression');
function match_Expression ($stack = array()) {
	$matchrule = "Expression"; $result = $this->construct($matchrule, $matchrule, null);
	$matcher = 'match_'.'ConditionalExpression'; $key = $matcher; $pos = $this->pos;
	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
	if ($subres !== false) {
		$this->store( $result, $subres, "exp" );
		return $this->finalise($result);
	}
	else { return false; }
}


/* SimpleExpression: term:WrappedExpression | term:NotExpression | term:ArrayLiteral | term:ObjectLiteral | term:Term */
protected $match_SimpleExpression_typestack = array('SimpleExpression');
function match_SimpleExpression ($stack = array()) {
	$matchrule = "SimpleExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_78 = NULL;
	do {
		$res_63 = $result;
		$pos_63 = $this->pos;
		$matcher = 'match_'.'WrappedExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "term" );
			$_78 = true; break;
		}
		$result = $res_63;
		$this->pos = $pos_63;
		$_76 = NULL;
		do {
			$res_65 = $result;
			$pos_65 = $this->pos;
			$matcher = 'match_'.'NotExpression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== false) {
				$this->store( $result, $subres, "term" );
				$_76 = true; break;
			}
			$result = $res_65;
			$this->pos = $pos_65;
			$_74 = NULL;
			do {
				$res_67 = $result;
				$pos_67 = $this->pos;
				$matcher = 'match_'.'ArrayLiteral'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "term" );
					$_74 = true; break;
				}
				$result = $res_67;
				$this->pos = $pos_67;
				$_72 = NULL;
				do {
					$res_69 = $result;
					$pos_69 = $this->pos;
					$matcher = 'match_'.'ObjectLiteral'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== false) {
						$this->store( $result, $subres, "term" );
						$_72 = true; break;
					}
					$result = $res_69;
					$this->pos = $pos_69;
					$matcher = 'match_'.'Term'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== false) {
						$this->store( $result, $subres, "term" );
						$_72 = true; break;
					}
					$result = $res_69;
					$this->pos = $pos_69;
					$_72 = false; break;
				}
				while(0);
				if( $_72 === true ) { $_74 = true; break; }
				$result = $res_67;
				$this->pos = $pos_67;
				$_74 = false; break;
			}
			while(0);
			if( $_74 === true ) { $_76 = true; break; }
			$result = $res_65;
			$this->pos = $pos_65;
			$_76 = false; break;
		}
		while(0);
		if( $_76 === true ) { $_78 = true; break; }
		$result = $res_63;
		$this->pos = $pos_63;
		$_78 = false; break;
	}
	while(0);
	if( $_78 === true ) { return $this->finalise($result); }
	if( $_78 === false) { return false; }
}


/* WrappedExpression: '(' < Expression > ')' */
protected $match_WrappedExpression_typestack = array('WrappedExpression');
function match_WrappedExpression ($stack = array()) {
	$matchrule = "WrappedExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_85 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '(') {
			$this->pos += 1;
			$result["text"] .= '(';
		}
		else { $_85 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_85 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ')') {
			$this->pos += 1;
			$result["text"] .= ')';
		}
		else { $_85 = false; break; }
		$_85 = true; break;
	}
	while(0);
	if( $_85 === true ) { return $this->finalise($result); }
	if( $_85 === false) { return false; }
}


/* NotExpression: (/ ! | not\s+ /) > exp:SimpleExpression */
protected $match_NotExpression_typestack = array('NotExpression');
function match_NotExpression ($stack = array()) {
	$matchrule = "NotExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_92 = NULL;
	do {
		$_88 = NULL;
		do {
			if (( $subres = $this->rx( '/ ! | not\s+ /' ) ) !== false) { $result["text"] .= $subres; }
			else { $_88 = false; break; }
			$_88 = true; break;
		}
		while(0);
		if( $_88 === false) { $_92 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "exp" );
		}
		else { $_92 = false; break; }
		$_92 = true; break;
	}
	while(0);
	if( $_92 === true ) { return $this->finalise($result); }
	if( $_92 === false) { return false; }
}


/* ConditionalExpression: cond:Disjunction (< '?' > then:Expression < ':' > else:Expression)? */
protected $match_ConditionalExpression_typestack = array('ConditionalExpression');
function match_ConditionalExpression ($stack = array()) {
	$matchrule = "ConditionalExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_105 = NULL;
	do {
		$matcher = 'match_'.'Disjunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "cond" );
		}
		else { $_105 = false; break; }
		$res_104 = $result;
		$pos_104 = $this->pos;
		$_103 = NULL;
		do {
			if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '?') {
				$this->pos += 1;
				$result["text"] .= '?';
			}
			else { $_103 = false; break; }
			if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== false) {
				$this->store( $result, $subres, "then" );
			}
			else { $_103 = false; break; }
			if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_103 = false; break; }
			if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== false) {
				$this->store( $result, $subres, "else" );
			}
			else { $_103 = false; break; }
			$_103 = true; break;
		}
		while(0);
		if( $_103 === false) {
			$result = $res_104;
			$this->pos = $pos_104;
			unset( $res_104 );
			unset( $pos_104 );
		}
		$_105 = true; break;
	}
	while(0);
	if( $_105 === true ) { return $this->finalise($result); }
	if( $_105 === false) { return false; }
}


/* Disjunction: lft:Conjunction (< / \|\| | or\s+ / > rgt:Conjunction)* */
protected $match_Disjunction_typestack = array('Disjunction');
function match_Disjunction ($stack = array()) {
	$matchrule = "Disjunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_114 = NULL;
	do {
		$matcher = 'match_'.'Conjunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_114 = false; break; }
		while (true) {
			$res_113 = $result;
			$pos_113 = $this->pos;
			$_112 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				if (( $subres = $this->rx( '/ \|\| | or\s+ /' ) ) !== false) { $result["text"] .= $subres; }
				else { $_112 = false; break; }
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Conjunction'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_112 = false; break; }
				$_112 = true; break;
			}
			while(0);
			if( $_112 === false) {
				$result = $res_113;
				$this->pos = $pos_113;
				unset( $res_113 );
				unset( $pos_113 );
				break;
			}
		}
		$_114 = true; break;
	}
	while(0);
	if( $_114 === true ) { return $this->finalise($result); }
	if( $_114 === false) { return false; }
}


/* Conjunction: lft:Comparison (< / && | and\s+ / > rgt:Comparison)* */
protected $match_Conjunction_typestack = array('Conjunction');
function match_Conjunction ($stack = array()) {
	$matchrule = "Conjunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_123 = NULL;
	do {
		$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_123 = false; break; }
		while (true) {
			$res_122 = $result;
			$pos_122 = $this->pos;
			$_121 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				if (( $subres = $this->rx( '/ && | and\s+ /' ) ) !== false) { $result["text"] .= $subres; }
				else { $_121 = false; break; }
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_121 = false; break; }
				$_121 = true; break;
			}
			while(0);
			if( $_121 === false) {
				$result = $res_122;
				$this->pos = $pos_122;
				unset( $res_122 );
				unset( $pos_122 );
				break;
			}
		}
		$_123 = true; break;
	}
	while(0);
	if( $_123 === true ) { return $this->finalise($result); }
	if( $_123 === false) { return false; }
}


/* Comparison: lft:SumCalculation (< comp:/ == | != | <= | >= | < | > / > rgt:SumCalculation)? */
protected $match_Comparison_typestack = array('Comparison');
function match_Comparison ($stack = array()) {
	$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
	$_132 = NULL;
	do {
		$matcher = 'match_'.'SumCalculation'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_132 = false; break; }
		$res_131 = $result;
		$pos_131 = $this->pos;
		$_130 = NULL;
		do {
			if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "comp" );
			if (( $subres = $this->rx( '/ == | != | <= | >= | < | > /' ) ) !== false) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'comp' );
			}
			else {
				$result = array_pop($stack);
				$_130 = false; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
			$matcher = 'match_'.'SumCalculation'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== false) {
				$this->store( $result, $subres, "rgt" );
			}
			else { $_130 = false; break; }
			$_130 = true; break;
		}
		while(0);
		if( $_130 === false) {
			$result = $res_131;
			$this->pos = $pos_131;
			unset( $res_131 );
			unset( $pos_131 );
		}
		$_132 = true; break;
	}
	while(0);
	if( $_132 === true ) { return $this->finalise($result); }
	if( $_132 === false) { return false; }
}


/* SumCalculation: lft:ProdCalculation (< op:/ \+ | \- / > rgt:ProdCalculation)* */
protected $match_SumCalculation_typestack = array('SumCalculation');
function match_SumCalculation ($stack = array()) {
	$matchrule = "SumCalculation"; $result = $this->construct($matchrule, $matchrule, null);
	$_141 = NULL;
	do {
		$matcher = 'match_'.'ProdCalculation'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_141 = false; break; }
		while (true) {
			$res_140 = $result;
			$pos_140 = $this->pos;
			$_139 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "op" );
				if (( $subres = $this->rx( '/ \+ | \- /' ) ) !== false) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'op' );
				}
				else {
					$result = array_pop($stack);
					$_139 = false; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'ProdCalculation'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_139 = false; break; }
				$_139 = true; break;
			}
			while(0);
			if( $_139 === false) {
				$result = $res_140;
				$this->pos = $pos_140;
				unset( $res_140 );
				unset( $pos_140 );
				break;
			}
		}
		$_141 = true; break;
	}
	while(0);
	if( $_141 === true ) { return $this->finalise($result); }
	if( $_141 === false) { return false; }
}


/* ProdCalculation: lft:SimpleExpression (< op:/ \/ | \* | % / > rgt:SimpleExpression)* */
protected $match_ProdCalculation_typestack = array('ProdCalculation');
function match_ProdCalculation ($stack = array()) {
	$matchrule = "ProdCalculation"; $result = $this->construct($matchrule, $matchrule, null);
	$_150 = NULL;
	do {
		$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_150 = false; break; }
		while (true) {
			$res_149 = $result;
			$pos_149 = $this->pos;
			$_148 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$stack[] = $result; $result = $this->construct( $matchrule, "op" );
				if (( $subres = $this->rx( '/ \/ | \* | % /' ) ) !== false) {
					$result["text"] .= $subres;
					$subres = $result; $result = array_pop($stack);
					$this->store( $result, $subres, 'op' );
				}
				else {
					$result = array_pop($stack);
					$_148 = false; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_148 = false; break; }
				$_148 = true; break;
			}
			while(0);
			if( $_148 === false) {
				$result = $res_149;
				$this->pos = $pos_149;
				unset( $res_149 );
				unset( $pos_149 );
				break;
			}
		}
		$_150 = true; break;
	}
	while(0);
	if( $_150 === true ) { return $this->finalise($result); }
	if( $_150 === false) { return false; }
}


/* ArrayLiteral: '[' _ < Expression? (< _ ',' _ > Expression)* > _ ']' */
protected $match_ArrayLiteral_typestack = array('ArrayLiteral');
function match_ArrayLiteral ($stack = array()) {
	$matchrule = "ArrayLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_167 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_167 = false; break; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_167 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$res_155 = $result;
		$pos_155 = $this->pos;
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else {
			$result = $res_155;
			$this->pos = $pos_155;
			unset( $res_155 );
			unset( $pos_155 );
		}
		while (true) {
			$res_163 = $result;
			$pos_163 = $this->pos;
			$_162 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_162 = false; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_162 = false; break; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_162 = false; break; }
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_162 = false; break; }
				$_162 = true; break;
			}
			while(0);
			if( $_162 === false) {
				$result = $res_163;
				$this->pos = $pos_163;
				unset( $res_163 );
				unset( $pos_163 );
				break;
			}
		}
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_167 = false; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_167 = false; break; }
		$_167 = true; break;
	}
	while(0);
	if( $_167 === true ) { return $this->finalise($result); }
	if( $_167 === false) { return false; }
}


/* ObjectLiteralProperty: key:(StringLiteral | Identifier) < ':' > value:Expression */
protected $match_ObjectLiteralProperty_typestack = array('ObjectLiteralProperty');
function match_ObjectLiteralProperty ($stack = array()) {
	$matchrule = "ObjectLiteralProperty"; $result = $this->construct($matchrule, $matchrule, null);
	$_180 = NULL;
	do {
		$stack[] = $result; $result = $this->construct( $matchrule, "key" );
		$_174 = NULL;
		do {
			$_172 = NULL;
			do {
				$res_169 = $result;
				$pos_169 = $this->pos;
				$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres );
					$_172 = true; break;
				}
				$result = $res_169;
				$this->pos = $pos_169;
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) {
					$this->store( $result, $subres );
					$_172 = true; break;
				}
				$result = $res_169;
				$this->pos = $pos_169;
				$_172 = false; break;
			}
			while(0);
			if( $_172 === false) { $_174 = false; break; }
			$_174 = true; break;
		}
		while(0);
		if( $_174 === true ) {
			$subres = $result; $result = array_pop($stack);
			$this->store( $result, $subres, 'key' );
		}
		if( $_174 === false) {
			$result = array_pop($stack);
			$_180 = false; break;
		}
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ':') {
			$this->pos += 1;
			$result["text"] .= ':';
		}
		else { $_180 = false; break; }
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "value" );
		}
		else { $_180 = false; break; }
		$_180 = true; break;
	}
	while(0);
	if( $_180 === true ) { return $this->finalise($result); }
	if( $_180 === false) { return false; }
}


/* ObjectLiteral: '{' _ ObjectLiteralProperty? (< _ ',' _ > ObjectLiteralProperty)* > _ '}' */
protected $match_ObjectLiteral_typestack = array('ObjectLiteral');
function match_ObjectLiteral ($stack = array()) {
	$matchrule = "ObjectLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_196 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_196 = false; break; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_196 = false; break; }
		$res_184 = $result;
		$pos_184 = $this->pos;
		$matcher = 'match_'.'ObjectLiteralProperty'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else {
			$result = $res_184;
			$this->pos = $pos_184;
			unset( $res_184 );
			unset( $pos_184 );
		}
		while (true) {
			$res_192 = $result;
			$pos_192 = $this->pos;
			$_191 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_191 = false; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_191 = false; break; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_191 = false; break; }
				if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
				$matcher = 'match_'.'ObjectLiteralProperty'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_191 = false; break; }
				$_191 = true; break;
			}
			while(0);
			if( $_191 === false) {
				$result = $res_192;
				$this->pos = $pos_192;
				unset( $res_192 );
				unset( $pos_192 );
				break;
			}
		}
		if (( $subres = $this->whitespace(  ) ) !== false) { $result["text"] .= $subres; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_196 = false; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_196 = false; break; }
		$_196 = true; break;
	}
	while(0);
	if( $_196 === true ) { return $this->finalise($result); }
	if( $_196 === false) { return false; }
}


/* _: / (\s|\n|\r)* / */
protected $match___typestack = array('_');
function match__ ($stack = array()) {
	$matchrule = "_"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ (\s|\n|\r)* /' ) ) !== false) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return false; }
}




}
