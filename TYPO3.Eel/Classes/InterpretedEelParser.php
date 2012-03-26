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

/**
 * An interpreting expression parser
 *
 * The matcher functions attached to the rules interpret the result
 * given the context in the constructor.
 */
class InterpretedEelParser extends EelParser {

	/**
	 * @var \TYPO3\Eel\Context
	 */
	protected $context;

	/**
	 * @param string $string
	 * @param \TYPO3\Eel\Context $context The context to interpret
	 */
	public function __construct($string, $context) {
		parent::__construct($string);
		$this->context = $context;
	}

	public function NumberLiteral__finalise(&$self) {
		if (isset($self['dec'])) {
			$self['val'] = (float)($self['text']);
		} else {
			$self['val'] = (integer)$self['text'];
		}
	}

	public function BooleanLiteral__finalise(&$result) {
		$result['val'] = strtolower($result['text']) === 'true';
	}

	public function OffsetAccess_Expression(&$result, $sub) {
		$result['index'] = $sub['val'];
	}

	public function MethodCall_Identifier(&$result, $sub) {
		$result['method'] = $sub['text'];
	}
	public function MethodCall_Expression(&$result, $sub) {
		$result['arguments'][] = $sub['val'];
	}

	public function ObjectPath_Identifier(&$result, $sub) {
		$path = $sub['text'];
		if (!array_key_exists('val', $result)) {
			$result['val'] = $this->context;
		}
		$result['val'] = $result['val']->getAndWrap($path);
	}

	public function ObjectPath_OffsetAccess(&$result, $sub) {
		$path = $sub['index'];
		$result['val'] = $result['val']->getAndWrap($path);
	}

	public function ObjectPath_MethodCall(&$result, $sub) {
		$arguments = isset($sub['arguments']) ? $sub['arguments'] : array();
		if (!array_key_exists('val', $result)) {
			$result['val'] = $this->context;
		}
		$result['val'] = $result['val']->callAndWrap($sub['method'], $arguments);
	}

	public function Term_term(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function Expression_Disjunction(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function SimpleExpression_term(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function WrappedExpression_Expression(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function NotExpression_Expression(&$result, $sub) {
		$result['val'] = !(boolean)$sub['val'];
	}

	public function ArrayLiteral_Expression(&$result, $sub) {
		if (!isset($result['val'])) {
			$result['val'] = new Context(array());
		}
		$result['val']->push($sub['val']);
	}

	public function Disjunction_lft(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function Disjunction_rgt(&$result, $sub) {
		$result['val'] = $result['val'] || (boolean)$sub['val'];
	}

	public function Conjunction_lft(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function Conjunction_rgt(&$result, $sub) {
		$result['val'] = $result['val'] && (boolean)$sub['val'];
	}

	public function Comparison_lft(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function Comparison_comp(&$result, $sub) {
		$result['comp'] = $sub['text'];
	}

	public function Comparison_rgt(&$result, $sub) {
		$rval = $sub['val'];
		switch ($result['comp']) {
		case '==':
			$result['val'] = $result['val'] === $rval;
			break;
		case '<':
			$result['val'] = $result['val'] < $rval;
			break;
		case '<=':
			$result['val'] = $result['val'] <= $rval;
			break;
		case '>':
			$result['val'] = $result['val'] > $rval;
			break;
		case '>=':
			$result['val'] = $result['val'] >= $rval;
			break;
		}
	}

	public function SumCalculation_lft(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function SumCalculation_op(&$result, $sub) {
		$result['op'] = $sub['text'];
	}

	public function SumCalculation_rgt(&$result, $sub) {
		$rval = $sub['val'];
		switch ($result['op']) {
		case '+':
			$result['val'] += $rval;
			break;
		case '-':
			$result['val'] -= $rval;
			break;
		}
	}

	public function ProdCalculation_lft(&$result, $sub) {
		$result['val'] = $sub['val'];
	}

	public function ProdCalculation_op(&$result, $sub) {
		$result['op'] = $sub['text'];
	}

	public function ProdCalculation_rgt(&$result, $sub) {
		$rval = $sub['val'];
		switch ($result['op']) {
		case '/':
			$result['val'] = $result['val'] / $rval;
			break;
		case '*':
			$result['val'] *= $rval;
			break;
		case '%':
			$result['val'] = $result['val'] % $rval;
			break;
		}
	}

}
?>