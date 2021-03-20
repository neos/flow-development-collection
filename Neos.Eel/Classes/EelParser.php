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


/* SimpleExpression: term:ArrowFunction | term:WrappedExpression | term:NotExpression | term:ArrayLiteral | term:ObjectLiteral | term:Term */
protected $match_SimpleExpression_typestack = array('SimpleExpression');
function match_SimpleExpression ($stack = array()) {
	$matchrule = "SimpleExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_82 = NULL;
	do {
		$res_63 = $result;
		$pos_63 = $this->pos;
		$matcher = 'match_'.'ArrowFunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "term" );
			$_82 = TRUE; break;
		}
		$result = $res_63;
		$this->pos = $pos_63;
		$_80 = NULL;
		do {
			$res_65 = $result;
			$pos_65 = $this->pos;
			$matcher = 'match_'.'WrappedExpression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "term" );
				$_80 = TRUE; break;
			}
			$result = $res_65;
			$this->pos = $pos_65;
			$_78 = NULL;
			do {
				$res_67 = $result;
				$pos_67 = $this->pos;
				$matcher = 'match_'.'NotExpression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "term" );
					$_78 = TRUE; break;
				}
				$result = $res_67;
				$this->pos = $pos_67;
				$_76 = NULL;
				do {
					$res_69 = $result;
					$pos_69 = $this->pos;
					$matcher = 'match_'.'ArrayLiteral'; $key = $matcher; $pos = $this->pos;
					$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
					if ($subres !== FALSE) {
						$this->store( $result, $subres, "term" );
						$_76 = TRUE; break;
					}
					$result = $res_69;
					$this->pos = $pos_69;
					$_74 = NULL;
					do {
						$res_71 = $result;
						$pos_71 = $this->pos;
						$matcher = 'match_'.'ObjectLiteral'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "term" );
							$_74 = TRUE; break;
						}
						$result = $res_71;
						$this->pos = $pos_71;
						$matcher = 'match_'.'Term'; $key = $matcher; $pos = $this->pos;
						$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
						if ($subres !== FALSE) {
							$this->store( $result, $subres, "term" );
							$_74 = TRUE; break;
						}
						$result = $res_71;
						$this->pos = $pos_71;
						$_74 = FALSE; break;
					}
					while(0);
					if( $_74 === TRUE ) { $_76 = TRUE; break; }
					$result = $res_69;
					$this->pos = $pos_69;
					$_76 = FALSE; break;
				}
				while(0);
				if( $_76 === TRUE ) { $_78 = TRUE; break; }
				$result = $res_67;
				$this->pos = $pos_67;
				$_78 = FALSE; break;
			}
			while(0);
			if( $_78 === TRUE ) { $_80 = TRUE; break; }
			$result = $res_65;
			$this->pos = $pos_65;
			$_80 = FALSE; break;
		}
		while(0);
		if( $_80 === TRUE ) { $_82 = TRUE; break; }
		$result = $res_63;
		$this->pos = $pos_63;
		$_82 = FALSE; break;
	}
	while(0);
	if( $_82 === TRUE ) { return $this->finalise($result); }
	if( $_82 === FALSE) { return FALSE; }
}


/* ArrowFunction: arguments:MethodArguments < '=>' > exp:Expression */
protected $match_ArrowFunction_typestack = array('ArrowFunction');
function match_ArrowFunction ($stack = array()) {
	$matchrule = "ArrowFunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_89 = NULL;
	do {
		$matcher = 'match_'.'MethodArguments'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "arguments" );
		}
		else { $_89 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (( $subres = $this->literal( '=>' ) ) !== FALSE) { $result["text"] .= $subres; }
		else { $_89 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "exp" );
		}
		else { $_89 = FALSE; break; }
		$_89 = TRUE; break;
	}
	while(0);
	if( $_89 === TRUE ) { return $this->finalise($result); }
	if( $_89 === FALSE) { return FALSE; }
}


/* MethodArguments: arguments:MethodArgumentsWithParens | arguments:MethodArgumentsWithoutParens */
protected $match_MethodArguments_typestack = array('MethodArguments');
function match_MethodArguments ($stack = array()) {
	$matchrule = "MethodArguments"; $result = $this->construct($matchrule, $matchrule, null);
	$_94 = NULL;
	do {
		$res_91 = $result;
		$pos_91 = $this->pos;
		$matcher = 'match_'.'MethodArgumentsWithParens'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "arguments" );
			$_94 = TRUE; break;
		}
		$result = $res_91;
		$this->pos = $pos_91;
		$matcher = 'match_'.'MethodArgumentsWithoutParens'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "arguments" );
			$_94 = TRUE; break;
		}
		$result = $res_91;
		$this->pos = $pos_91;
		$_94 = FALSE; break;
	}
	while(0);
	if( $_94 === TRUE ) { return $this->finalise($result); }
	if( $_94 === FALSE) { return FALSE; }
}


/* MethodArgumentsWithParens: '(' < Identifier? (< ',' > Identifier)* > ')' */
protected $match_MethodArgumentsWithParens_typestack = array('MethodArgumentsWithParens');
function match_MethodArgumentsWithParens ($stack = array()) {
	$matchrule = "MethodArgumentsWithParens"; $result = $this->construct($matchrule, $matchrule, null);
	$_107 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '(') {
			$this->pos += 1;
			$result["text"] .= '(';
		}
		else { $_107 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$res_98 = $result;
		$pos_98 = $this->pos;
		$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else {
			$result = $res_98;
			$this->pos = $pos_98;
			unset( $res_98 );
			unset( $pos_98 );
		}
		while (true) {
			$res_104 = $result;
			$pos_104 = $this->pos;
			$_103 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_103 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_103 = FALSE; break; }
				$_103 = TRUE; break;
			}
			while(0);
			if( $_103 === FALSE) {
				$result = $res_104;
				$this->pos = $pos_104;
				unset( $res_104 );
				unset( $pos_104 );
				break;
			}
		}
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ')') {
			$this->pos += 1;
			$result["text"] .= ')';
		}
		else { $_107 = FALSE; break; }
		$_107 = TRUE; break;
	}
	while(0);
	if( $_107 === TRUE ) { return $this->finalise($result); }
	if( $_107 === FALSE) { return FALSE; }
}


/* MethodArgumentsWithoutParens: Identifier */
protected $match_MethodArgumentsWithoutParens_typestack = array('MethodArgumentsWithoutParens');
function match_MethodArgumentsWithoutParens ($stack = array()) {
	$matchrule = "MethodArgumentsWithoutParens"; $result = $this->construct($matchrule, $matchrule, null);
	$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
	$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
	if ($subres !== FALSE) {
		$this->store( $result, $subres );
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* WrappedExpression: '(' < Expression > ')' */
protected $match_WrappedExpression_typestack = array('WrappedExpression');
function match_WrappedExpression ($stack = array()) {
	$matchrule = "WrappedExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_115 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '(') {
			$this->pos += 1;
			$result["text"] .= '(';
		}
		else { $_115 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_115 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ')') {
			$this->pos += 1;
			$result["text"] .= ')';
		}
		else { $_115 = FALSE; break; }
		$_115 = TRUE; break;
	}
	while(0);
	if( $_115 === TRUE ) { return $this->finalise($result); }
	if( $_115 === FALSE) { return FALSE; }
}


/* NotExpression: (/ ! | not\s+ /) > exp:SimpleExpression */
protected $match_NotExpression_typestack = array('NotExpression');
function match_NotExpression ($stack = array()) {
	$matchrule = "NotExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_122 = NULL;
	do {
		$_118 = NULL;
		do {
			if (( $subres = $this->rx( '/ ! | not\s+ /' ) ) !== FALSE) { $result["text"] .= $subres; }
			else { $_118 = FALSE; break; }
			$_118 = TRUE; break;
		}
		while(0);
		if( $_118 === FALSE) { $_122 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "exp" );
		}
		else { $_122 = FALSE; break; }
		$_122 = TRUE; break;
	}
	while(0);
	if( $_122 === TRUE ) { return $this->finalise($result); }
	if( $_122 === FALSE) { return FALSE; }
}


/* ConditionalExpression: cond:Disjunction (< '?' > then:Expression < ':' > else:Expression)? */
protected $match_ConditionalExpression_typestack = array('ConditionalExpression');
function match_ConditionalExpression ($stack = array()) {
	$matchrule = "ConditionalExpression"; $result = $this->construct($matchrule, $matchrule, null);
	$_135 = NULL;
	do {
		$matcher = 'match_'.'Disjunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "cond" );
		}
		else { $_135 = FALSE; break; }
		$res_134 = $result;
		$pos_134 = $this->pos;
		$_133 = NULL;
		do {
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == '?') {
				$this->pos += 1;
				$result["text"] .= '?';
			}
			else { $_133 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "then" );
			}
			else { $_133 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			if (substr($this->string,$this->pos,1) == ':') {
				$this->pos += 1;
				$result["text"] .= ':';
			}
			else { $_133 = FALSE; break; }
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "else" );
			}
			else { $_133 = FALSE; break; }
			$_133 = TRUE; break;
		}
		while(0);
		if( $_133 === FALSE) {
			$result = $res_134;
			$this->pos = $pos_134;
			unset( $res_134 );
			unset( $pos_134 );
		}
		$_135 = TRUE; break;
	}
	while(0);
	if( $_135 === TRUE ) { return $this->finalise($result); }
	if( $_135 === FALSE) { return FALSE; }
}


/* Disjunction: lft:Conjunction (< / \|\| | or\s+ / > rgt:Conjunction)* */
protected $match_Disjunction_typestack = array('Disjunction');
function match_Disjunction ($stack = array()) {
	$matchrule = "Disjunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_144 = NULL;
	do {
		$matcher = 'match_'.'Conjunction'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_144 = FALSE; break; }
		while (true) {
			$res_143 = $result;
			$pos_143 = $this->pos;
			$_142 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->rx( '/ \|\| | or\s+ /' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_142 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Conjunction'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_142 = FALSE; break; }
				$_142 = TRUE; break;
			}
			while(0);
			if( $_142 === FALSE) {
				$result = $res_143;
				$this->pos = $pos_143;
				unset( $res_143 );
				unset( $pos_143 );
				break;
			}
		}
		$_144 = TRUE; break;
	}
	while(0);
	if( $_144 === TRUE ) { return $this->finalise($result); }
	if( $_144 === FALSE) { return FALSE; }
}


/* Conjunction: lft:Comparison (< / && | and\s+ / > rgt:Comparison)* */
protected $match_Conjunction_typestack = array('Conjunction');
function match_Conjunction ($stack = array()) {
	$matchrule = "Conjunction"; $result = $this->construct($matchrule, $matchrule, null);
	$_153 = NULL;
	do {
		$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_153 = FALSE; break; }
		while (true) {
			$res_152 = $result;
			$pos_152 = $this->pos;
			$_151 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				if (( $subres = $this->rx( '/ && | and\s+ /' ) ) !== FALSE) { $result["text"] .= $subres; }
				else { $_151 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Comparison'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_151 = FALSE; break; }
				$_151 = TRUE; break;
			}
			while(0);
			if( $_151 === FALSE) {
				$result = $res_152;
				$this->pos = $pos_152;
				unset( $res_152 );
				unset( $pos_152 );
				break;
			}
		}
		$_153 = TRUE; break;
	}
	while(0);
	if( $_153 === TRUE ) { return $this->finalise($result); }
	if( $_153 === FALSE) { return FALSE; }
}


/* Comparison: lft:SumCalculation (< comp:/ == | != | <= | >= | < | > / > rgt:SumCalculation)? */
protected $match_Comparison_typestack = array('Comparison');
function match_Comparison ($stack = array()) {
	$matchrule = "Comparison"; $result = $this->construct($matchrule, $matchrule, null);
	$_162 = NULL;
	do {
		$matcher = 'match_'.'SumCalculation'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_162 = FALSE; break; }
		$res_161 = $result;
		$pos_161 = $this->pos;
		$_160 = NULL;
		do {
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$stack[] = $result; $result = $this->construct( $matchrule, "comp" );
			if (( $subres = $this->rx( '/ == | != | <= | >= | < | > /' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$subres = $result; $result = array_pop($stack);
				$this->store( $result, $subres, 'comp' );
			}
			else {
				$result = array_pop($stack);
				$_160 = FALSE; break;
			}
			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
			$matcher = 'match_'.'SumCalculation'; $key = $matcher; $pos = $this->pos;
			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
			if ($subres !== FALSE) {
				$this->store( $result, $subres, "rgt" );
			}
			else { $_160 = FALSE; break; }
			$_160 = TRUE; break;
		}
		while(0);
		if( $_160 === FALSE) {
			$result = $res_161;
			$this->pos = $pos_161;
			unset( $res_161 );
			unset( $pos_161 );
		}
		$_162 = TRUE; break;
	}
	while(0);
	if( $_162 === TRUE ) { return $this->finalise($result); }
	if( $_162 === FALSE) { return FALSE; }
}


/* SumCalculation: lft:ProdCalculation (< op:/ \+ | \- / > rgt:ProdCalculation)* */
protected $match_SumCalculation_typestack = array('SumCalculation');
function match_SumCalculation ($stack = array()) {
	$matchrule = "SumCalculation"; $result = $this->construct($matchrule, $matchrule, null);
	$_171 = NULL;
	do {
		$matcher = 'match_'.'ProdCalculation'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_171 = FALSE; break; }
		while (true) {
			$res_170 = $result;
			$pos_170 = $this->pos;
			$_169 = NULL;
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
					$_169 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'ProdCalculation'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_169 = FALSE; break; }
				$_169 = TRUE; break;
			}
			while(0);
			if( $_169 === FALSE) {
				$result = $res_170;
				$this->pos = $pos_170;
				unset( $res_170 );
				unset( $pos_170 );
				break;
			}
		}
		$_171 = TRUE; break;
	}
	while(0);
	if( $_171 === TRUE ) { return $this->finalise($result); }
	if( $_171 === FALSE) { return FALSE; }
}


/* ProdCalculation: lft:SimpleExpression (< op:/ \/ | \* | % / > rgt:SimpleExpression)* */
protected $match_ProdCalculation_typestack = array('ProdCalculation');
function match_ProdCalculation ($stack = array()) {
	$matchrule = "ProdCalculation"; $result = $this->construct($matchrule, $matchrule, null);
	$_180 = NULL;
	do {
		$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "lft" );
		}
		else { $_180 = FALSE; break; }
		while (true) {
			$res_179 = $result;
			$pos_179 = $this->pos;
			$_178 = NULL;
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
					$_178 = FALSE; break;
				}
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'SimpleExpression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres, "rgt" );
				}
				else { $_178 = FALSE; break; }
				$_178 = TRUE; break;
			}
			while(0);
			if( $_178 === FALSE) {
				$result = $res_179;
				$this->pos = $pos_179;
				unset( $res_179 );
				unset( $pos_179 );
				break;
			}
		}
		$_180 = TRUE; break;
	}
	while(0);
	if( $_180 === TRUE ) { return $this->finalise($result); }
	if( $_180 === FALSE) { return FALSE; }
}


/* ArrayLiteral: '[' _ < Expression? (< _ ',' _ > Expression)* > _ ']' */
protected $match_ArrayLiteral_typestack = array('ArrayLiteral');
function match_ArrayLiteral ($stack = array()) {
	$matchrule = "ArrayLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_197 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '[') {
			$this->pos += 1;
			$result["text"] .= '[';
		}
		else { $_197 = FALSE; break; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_197 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$res_185 = $result;
		$pos_185 = $this->pos;
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else {
			$result = $res_185;
			$this->pos = $pos_185;
			unset( $res_185 );
			unset( $pos_185 );
		}
		while (true) {
			$res_193 = $result;
			$pos_193 = $this->pos;
			$_192 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_192 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_192 = FALSE; break; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_192 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_192 = FALSE; break; }
				$_192 = TRUE; break;
			}
			while(0);
			if( $_192 === FALSE) {
				$result = $res_193;
				$this->pos = $pos_193;
				unset( $res_193 );
				unset( $pos_193 );
				break;
			}
		}
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_197 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == ']') {
			$this->pos += 1;
			$result["text"] .= ']';
		}
		else { $_197 = FALSE; break; }
		$_197 = TRUE; break;
	}
	while(0);
	if( $_197 === TRUE ) { return $this->finalise($result); }
	if( $_197 === FALSE) { return FALSE; }
}


/* ObjectLiteralProperty: key:(StringLiteral | Identifier) < ':' > value:Expression */
protected $match_ObjectLiteralProperty_typestack = array('ObjectLiteralProperty');
function match_ObjectLiteralProperty ($stack = array()) {
	$matchrule = "ObjectLiteralProperty"; $result = $this->construct($matchrule, $matchrule, null);
	$_210 = NULL;
	do {
		$stack[] = $result; $result = $this->construct( $matchrule, "key" );
		$_204 = NULL;
		do {
			$_202 = NULL;
			do {
				$res_199 = $result;
				$pos_199 = $this->pos;
				$matcher = 'match_'.'StringLiteral'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres );
					$_202 = TRUE; break;
				}
				$result = $res_199;
				$this->pos = $pos_199;
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) {
					$this->store( $result, $subres );
					$_202 = TRUE; break;
				}
				$result = $res_199;
				$this->pos = $pos_199;
				$_202 = FALSE; break;
			}
			while(0);
			if( $_202 === FALSE) { $_204 = FALSE; break; }
			$_204 = TRUE; break;
		}
		while(0);
		if( $_204 === TRUE ) {
			$subres = $result; $result = array_pop($stack);
			$this->store( $result, $subres, 'key' );
		}
		if( $_204 === FALSE) {
			$result = array_pop($stack);
			$_210 = FALSE; break;
		}
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		if (substr($this->string,$this->pos,1) == ':') {
			$this->pos += 1;
			$result["text"] .= ':';
		}
		else { $_210 = FALSE; break; }
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'Expression'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "value" );
		}
		else { $_210 = FALSE; break; }
		$_210 = TRUE; break;
	}
	while(0);
	if( $_210 === TRUE ) { return $this->finalise($result); }
	if( $_210 === FALSE) { return FALSE; }
}


/* ObjectLiteral: '{' _ ObjectLiteralProperty? (< _ ',' _ > ObjectLiteralProperty)* > _ '}' */
protected $match_ObjectLiteral_typestack = array('ObjectLiteral');
function match_ObjectLiteral ($stack = array()) {
	$matchrule = "ObjectLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_226 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '{') {
			$this->pos += 1;
			$result["text"] .= '{';
		}
		else { $_226 = FALSE; break; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_226 = FALSE; break; }
		$res_214 = $result;
		$pos_214 = $this->pos;
		$matcher = 'match_'.'ObjectLiteralProperty'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else {
			$result = $res_214;
			$this->pos = $pos_214;
			unset( $res_214 );
			unset( $pos_214 );
		}
		while (true) {
			$res_222 = $result;
			$pos_222 = $this->pos;
			$_221 = NULL;
			do {
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_221 = FALSE; break; }
				if (substr($this->string,$this->pos,1) == ',') {
					$this->pos += 1;
					$result["text"] .= ',';
				}
				else { $_221 = FALSE; break; }
				$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_221 = FALSE; break; }
				if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
				$matcher = 'match_'.'ObjectLiteralProperty'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== FALSE) { $this->store( $result, $subres ); }
				else { $_221 = FALSE; break; }
				$_221 = TRUE; break;
			}
			while(0);
			if( $_221 === FALSE) {
				$result = $res_222;
				$this->pos = $pos_222;
				unset( $res_222 );
				unset( $pos_222 );
				break;
			}
		}
		if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
		$matcher = 'match_'.'_'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) { $this->store( $result, $subres ); }
		else { $_226 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '}') {
			$this->pos += 1;
			$result["text"] .= '}';
		}
		else { $_226 = FALSE; break; }
		$_226 = TRUE; break;
	}
	while(0);
	if( $_226 === TRUE ) { return $this->finalise($result); }
	if( $_226 === FALSE) { return FALSE; }
}


/* _: / (\s|\n|\r)* / */
protected $match___typestack = array('_');
function match__ ($stack = array()) {
	$matchrule = "_"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ (\s|\n|\r)* /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}




}

