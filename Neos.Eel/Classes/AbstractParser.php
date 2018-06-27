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

require_once __DIR__ . '/../Resources/Private/PHP/php-peg/Parser.php';

/*
WARNING: This file has been machine generated. Do not edit it, or your changes will be overwritten next time it is compiled.
*/


/**
 * This Abstract Parser class contains definitions for absolutely basic types,
 * like quoted strings or identifiers
 *
 * @Neos\Flow\Annotations\Proxy(false)
 */
abstract class AbstractParser extends \PhpPeg\Parser {
/* S: / \s* / */
protected $match_S_typestack = array('S');
function match_S ($stack = array()) {
	$matchrule = "S"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ \s* /' ) ) !== false) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return false; }
}




/* _IntegerNumber: / -? [0-9]+ / */
protected $match__IntegerNumber_typestack = array('_IntegerNumber');
function match__IntegerNumber ($stack = array()) {
	$matchrule = "_IntegerNumber"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ -? [0-9]+ /' ) ) !== false) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return false; }
}


/* _Decimals: / \.[0-9]+ / */
protected $match__Decimals_typestack = array('_Decimals');
function match__Decimals ($stack = array()) {
	$matchrule = "_Decimals"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ \.[0-9]+ /' ) ) !== false) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return false; }
}


/* NumberLiteral: int:_IntegerNumber dec:_Decimals? */
protected $match_NumberLiteral_typestack = array('NumberLiteral');
function match_NumberLiteral ($stack = array()) {
	$matchrule = "NumberLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_5 = NULL;
	do {
		$matcher = 'match_'.'_IntegerNumber'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "int" );
		}
		else { $_5 = false; break; }
		$res_4 = $result;
		$pos_4 = $this->pos;
		$matcher = 'match_'.'_Decimals'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres, "dec" );
		}
		else {
			$result = $res_4;
			$this->pos = $pos_4;
			unset( $res_4 );
			unset( $pos_4 );
		}
		$_5 = true; break;
	}
	while(0);
	if( $_5 === true ) { return $this->finalise($result); }
	if( $_5 === false) { return false; }
}


/* DoubleQuotedStringLiteral: '"' / (\\"|[^"])* / '"' */
protected $match_DoubleQuotedStringLiteral_typestack = array('DoubleQuotedStringLiteral');
function match_DoubleQuotedStringLiteral ($stack = array()) {
	$matchrule = "DoubleQuotedStringLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_10 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '"') {
			$this->pos += 1;
			$result["text"] .= '"';
		}
		else { $_10 = false; break; }
		if (( $subres = $this->rx( '/ (\\\\"|[^"])* /' ) ) !== false) { $result["text"] .= $subres; }
		else { $_10 = false; break; }
		if (substr($this->string,$this->pos,1) == '"') {
			$this->pos += 1;
			$result["text"] .= '"';
		}
		else { $_10 = false; break; }
		$_10 = true; break;
	}
	while(0);
	if( $_10 === true ) { return $this->finalise($result); }
	if( $_10 === false) { return false; }
}


/* SingleQuotedStringLiteral: "\'" / (\\'|[^'])* / "\'" */
protected $match_SingleQuotedStringLiteral_typestack = array('SingleQuotedStringLiteral');
function match_SingleQuotedStringLiteral ($stack = array()) {
	$matchrule = "SingleQuotedStringLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_15 = NULL;
	do {
		if (substr($this->string,$this->pos,1) == '\'') {
			$this->pos += 1;
			$result["text"] .= '\'';
		}
		else { $_15 = false; break; }
		if (( $subres = $this->rx( '/ (\\\\\'|[^\'])* /' ) ) !== false) { $result["text"] .= $subres; }
		else { $_15 = false; break; }
		if (substr($this->string,$this->pos,1) == '\'') {
			$this->pos += 1;
			$result["text"] .= '\'';
		}
		else { $_15 = false; break; }
		$_15 = true; break;
	}
	while(0);
	if( $_15 === true ) { return $this->finalise($result); }
	if( $_15 === false) { return false; }
}


/* StringLiteral: SingleQuotedStringLiteral | DoubleQuotedStringLiteral */
protected $match_StringLiteral_typestack = array('StringLiteral');
function match_StringLiteral ($stack = array()) {
	$matchrule = "StringLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_20 = NULL;
	do {
		$res_17 = $result;
		$pos_17 = $this->pos;
		$matcher = 'match_'.'SingleQuotedStringLiteral'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres );
			$_20 = true; break;
		}
		$result = $res_17;
		$this->pos = $pos_17;
		$matcher = 'match_'.'DoubleQuotedStringLiteral'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) {
			$this->store( $result, $subres );
			$_20 = true; break;
		}
		$result = $res_17;
		$this->pos = $pos_17;
		$_20 = false; break;
	}
	while(0);
	if( $_20 === true ) { return $this->finalise($result); }
	if( $_20 === false) { return false; }
}


/* BooleanLiteral: 'true' | 'true' | 'false' | 'false' */
protected $match_BooleanLiteral_typestack = array('BooleanLiteral');
function match_BooleanLiteral ($stack = array()) {
	$matchrule = "BooleanLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_33 = NULL;
	do {
		$res_22 = $result;
		$pos_22 = $this->pos;
		if (( $subres = $this->literal( 'true' ) ) !== false) {
			$result["text"] .= $subres;
			$_33 = true; break;
		}
		$result = $res_22;
		$this->pos = $pos_22;
		$_31 = NULL;
		do {
			$res_24 = $result;
			$pos_24 = $this->pos;
			if (( $subres = $this->literal( 'true' ) ) !== false) {
				$result["text"] .= $subres;
				$_31 = true; break;
			}
			$result = $res_24;
			$this->pos = $pos_24;
			$_29 = NULL;
			do {
				$res_26 = $result;
				$pos_26 = $this->pos;
				if (( $subres = $this->literal( 'false' ) ) !== false) {
					$result["text"] .= $subres;
					$_29 = true; break;
				}
				$result = $res_26;
				$this->pos = $pos_26;
				if (( $subres = $this->literal( 'false' ) ) !== false) {
					$result["text"] .= $subres;
					$_29 = true; break;
				}
				$result = $res_26;
				$this->pos = $pos_26;
				$_29 = false; break;
			}
			while(0);
			if( $_29 === true ) { $_31 = true; break; }
			$result = $res_24;
			$this->pos = $pos_24;
			$_31 = false; break;
		}
		while(0);
		if( $_31 === true ) { $_33 = true; break; }
		$result = $res_22;
		$this->pos = $pos_22;
		$_33 = false; break;
	}
	while(0);
	if( $_33 === true ) { return $this->finalise($result); }
	if( $_33 === false) { return false; }
}


/* Identifier: / [a-zA-Z_] [a-zA-Z0-9_\-]* / */
protected $match_Identifier_typestack = array('Identifier');
function match_Identifier ($stack = array()) {
	$matchrule = "Identifier"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ [a-zA-Z_] [a-zA-Z0-9_\-]* /' ) ) !== false) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return false; }
}


/* PropertyPath: Identifier ( '.' Identifier )* */
protected $match_PropertyPath_typestack = array('PropertyPath');
function match_PropertyPath ($stack = array()) {
	$matchrule = "PropertyPath"; $result = $this->construct($matchrule, $matchrule, null);
	$_41 = NULL;
	do {
		$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== false) { $this->store( $result, $subres ); }
		else { $_41 = false; break; }
		while (true) {
			$res_40 = $result;
			$pos_40 = $this->pos;
			$_39 = NULL;
			do {
				if (substr($this->string,$this->pos,1) == '.') {
					$this->pos += 1;
					$result["text"] .= '.';
				}
				else { $_39 = false; break; }
				$matcher = 'match_'.'Identifier'; $key = $matcher; $pos = $this->pos;
				$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
				if ($subres !== false) { $this->store( $result, $subres ); }
				else { $_39 = false; break; }
				$_39 = true; break;
			}
			while(0);
			if( $_39 === false) {
				$result = $res_40;
				$this->pos = $pos_40;
				unset( $res_40 );
				unset( $pos_40 );
				break;
			}
		}
		$_41 = true; break;
	}
	while(0);
	if( $_41 === true ) { return $this->finalise($result); }
	if( $_41 === false) { return false; }
}





    public function StringLiteral_SingleQuotedStringLiteral(&$result, $sub)
    {
        $result['val'] = (string)str_replace("'", "'", substr($sub['text'], 1, -1));
    }

    public function StringLiteral_DoubleQuotedStringLiteral(&$result, $sub)
    {
        $result['val'] = (string)str_replace('\"', '"', substr($sub['text'], 1, -1));
    }
}
