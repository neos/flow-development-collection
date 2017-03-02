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
	if (( $subres = $this->rx( '/ \s* /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}




/* _IntegerNumber: / -? [0-9]+ / */
protected $match__IntegerNumber_typestack = array('_IntegerNumber');
function match__IntegerNumber ($stack = array()) {
	$matchrule = "_IntegerNumber"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ -? [0-9]+ /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* _Decimals: / \.[0-9]+ / */
protected $match__Decimals_typestack = array('_Decimals');
function match__Decimals ($stack = array()) {
	$matchrule = "_Decimals"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ \.[0-9]+ /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
}


/* NumberLiteral: int:_IntegerNumber dec:_Decimals? */
protected $match_NumberLiteral_typestack = array('NumberLiteral');
function match_NumberLiteral ($stack = array()) {
	$matchrule = "NumberLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_5 = NULL;
	do {
		$matcher = 'match_'.'_IntegerNumber'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "int" );
		}
		else { $_5 = FALSE; break; }
		$res_4 = $result;
		$pos_4 = $this->pos;
		$matcher = 'match_'.'_Decimals'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres, "dec" );
		}
		else {
			$result = $res_4;
			$this->pos = $pos_4;
			unset( $res_4 );
			unset( $pos_4 );
		}
		$_5 = TRUE; break;
	}
	while(0);
	if( $_5 === TRUE ) { return $this->finalise($result); }
	if( $_5 === FALSE) { return FALSE; }
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
		else { $_10 = FALSE; break; }
		if (( $subres = $this->rx( '/ (\\\\"|[^"])* /' ) ) !== FALSE) { $result["text"] .= $subres; }
		else { $_10 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '"') {
			$this->pos += 1;
			$result["text"] .= '"';
		}
		else { $_10 = FALSE; break; }
		$_10 = TRUE; break;
	}
	while(0);
	if( $_10 === TRUE ) { return $this->finalise($result); }
	if( $_10 === FALSE) { return FALSE; }
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
		else { $_15 = FALSE; break; }
		if (( $subres = $this->rx( '/ (\\\\\'|[^\'])* /' ) ) !== FALSE) { $result["text"] .= $subres; }
		else { $_15 = FALSE; break; }
		if (substr($this->string,$this->pos,1) == '\'') {
			$this->pos += 1;
			$result["text"] .= '\'';
		}
		else { $_15 = FALSE; break; }
		$_15 = TRUE; break;
	}
	while(0);
	if( $_15 === TRUE ) { return $this->finalise($result); }
	if( $_15 === FALSE) { return FALSE; }
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
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			$_20 = TRUE; break;
		}
		$result = $res_17;
		$this->pos = $pos_17;
		$matcher = 'match_'.'DoubleQuotedStringLiteral'; $key = $matcher; $pos = $this->pos;
		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
		if ($subres !== FALSE) {
			$this->store( $result, $subres );
			$_20 = TRUE; break;
		}
		$result = $res_17;
		$this->pos = $pos_17;
		$_20 = FALSE; break;
	}
	while(0);
	if( $_20 === TRUE ) { return $this->finalise($result); }
	if( $_20 === FALSE) { return FALSE; }
}


/* BooleanLiteral: 'true' | 'TRUE' | 'false' | 'FALSE' */
protected $match_BooleanLiteral_typestack = array('BooleanLiteral');
function match_BooleanLiteral ($stack = array()) {
	$matchrule = "BooleanLiteral"; $result = $this->construct($matchrule, $matchrule, null);
	$_33 = NULL;
	do {
		$res_22 = $result;
		$pos_22 = $this->pos;
		if (( $subres = $this->literal( 'true' ) ) !== FALSE) {
			$result["text"] .= $subres;
			$_33 = TRUE; break;
		}
		$result = $res_22;
		$this->pos = $pos_22;
		$_31 = NULL;
		do {
			$res_24 = $result;
			$pos_24 = $this->pos;
			if (( $subres = $this->literal( 'TRUE' ) ) !== FALSE) {
				$result["text"] .= $subres;
				$_31 = TRUE; break;
			}
			$result = $res_24;
			$this->pos = $pos_24;
			$_29 = NULL;
			do {
				$res_26 = $result;
				$pos_26 = $this->pos;
				if (( $subres = $this->literal( 'false' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_29 = TRUE; break;
				}
				$result = $res_26;
				$this->pos = $pos_26;
				if (( $subres = $this->literal( 'FALSE' ) ) !== FALSE) {
					$result["text"] .= $subres;
					$_29 = TRUE; break;
				}
				$result = $res_26;
				$this->pos = $pos_26;
				$_29 = FALSE; break;
			}
			while(0);
			if( $_29 === TRUE ) { $_31 = TRUE; break; }
			$result = $res_24;
			$this->pos = $pos_24;
			$_31 = FALSE; break;
		}
		while(0);
		if( $_31 === TRUE ) { $_33 = TRUE; break; }
		$result = $res_22;
		$this->pos = $pos_22;
		$_33 = FALSE; break;
	}
	while(0);
	if( $_33 === TRUE ) { return $this->finalise($result); }
	if( $_33 === FALSE) { return FALSE; }
}


/* Identifier: / [a-zA-Z_] [a-zA-Z0-9_\-]* / */
protected $match_Identifier_typestack = array('Identifier');
function match_Identifier ($stack = array()) {
	$matchrule = "Identifier"; $result = $this->construct($matchrule, $matchrule, null);
	if (( $subres = $this->rx( '/ [a-zA-Z_] [a-zA-Z0-9_\-]* /' ) ) !== FALSE) {
		$result["text"] .= $subres;
		return $this->finalise($result);
	}
	else { return FALSE; }
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
