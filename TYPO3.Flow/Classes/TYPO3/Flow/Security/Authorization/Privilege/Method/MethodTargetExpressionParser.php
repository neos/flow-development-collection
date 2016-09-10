<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
class MethodTargetExpressionParser extends PointcutExpressionParser
{
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
    protected function parseDesignatorPointcut($operator, $pointcutExpression, PointcutFilterComposite $pointcutFilterComposite, array &$trace = [])
    {
        throw new InvalidPointcutExpressionException('The given method privilege target matcher contained an expression for a named pointcut. This not supported! Given expression: "' . $pointcutExpression . '".', 1222014591);
    }
}
