<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\CompilingEelParser;
use TYPO3\Eel\ParserException;

/**
 * A compiling expression parser
 *
 * The matcher functions will generate PHP code according to the expressions.
 * Method calls and object / array access are wrapped using the Context object.
 */
class EntityPrivilegeExpressionParser extends CompilingEelParser {

	/**
	 * @param array $result
	 * @param array $sub
	 */
	public function NotExpression_exp(&$result, $sub) {
		if (!isset($result['code'])) {
			$result['code'] = '$context';
		}
		$result['code'] .= '->callAndWrap(\'notExpression\', array('. $this->unwrapExpression($sub['code']) . '))';
	}

	/**
	 * @param array $result
	 * @param array $sub
	 */
	public function Disjunction_rgt(&$result, $sub) {
		$result['code'] = '$context->callAndWrap(\'disjunction\', array('. $this->unwrapExpression($result['code']) . ', ' . $this->unwrapExpression($sub['code']) . '))';
	}

	/**
	 * @param array $result
	 * @param array $sub
	 */
	public function Conjunction_rgt(&$result, $sub) {
		$result['code'] = '$context->callAndWrap(\'conjunction\', array('. $this->unwrapExpression($result['code']) . ', ' . $this->unwrapExpression($sub['code']) . '))';
	}

	/**
	 * @param array $result
	 * @param array $sub
	 * @throws ParserException
	 */
	public function Comparison_rgt(&$result, $sub) {
		$lval = $result['code'];
		$rval = $sub['code'];

		if (strpos($lval, '$context->callAndWrap(\'property\'') === FALSE) {
			$temp = $rval;
			$rval = $lval;
			$lval = $temp;
		}

		switch ($result['comp']) {
			case '==':
				$compMethod = 'equals';
				break;
			case '!=':
				$compMethod = 'notEquals';
				break;
			case '<':
				$compMethod = 'lessThan';
				break;
			case '<=':
				$compMethod = 'lessOrEqual';
				break;
			case '>':
				$compMethod = 'greaterThan';
				break;
			case '>=':
				$compMethod = 'greaterOrEqual';
				break;
			default:
				throw new ParserException('Unexpected comparison operator "' . $result['comp'] . '"', 1344512571);
		}

		$result['code'] = $lval . '->callAndWrap(\'' . $compMethod . '\', array(' . $this->unwrapExpression($rval) . '))';
	}
}
