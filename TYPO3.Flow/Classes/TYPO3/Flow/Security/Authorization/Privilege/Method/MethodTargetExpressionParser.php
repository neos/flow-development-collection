<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException;
use TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;

/**
 * A specialized pointcut expression parser tailored to policy expressions
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class MethodTargetExpressionParser extends PointcutExpressionParser {

	/**
	 * Throws an exception, as recursive privilege targets are not allowed.
	 *
	 * @param string $operator The operator
	 * @param string $pointcutExpression The pointcut expression (value of the designator)
	 * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the pointcut filter) will be added to this composite object.
	 * @param array &$trace
	 * @return void
	 * @throws InvalidPointcutExpressionException
	 */
	protected function parseDesignatorPointcut($operator, $pointcutExpression, PointcutFilterComposite $pointcutFilterComposite, array &$trace = array()) {
		throw new InvalidPointcutExpressionException('The given method privilege target matcher contained an expression for a named pointcut. This not supported! Given expression: "' . $pointcutExpression . '".', 1222014591);
	}
}
