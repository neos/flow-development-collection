<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

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
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * An aspect for testing different kinds of pointcut expressions
 *
 * @Flow\Aspect
 */
class PointcutExpressionTestingAspect
{
    /**
     *
     * @Flow\Around("method(TYPO3\Flow\Tests\Functional\Aop\Fixtures\PointcutExpressionTestingTarget->testSettingFilter()) && setting(TYPO3.Flow.tests.functional.aop.pointcutExpressionSettingFilterOptionA)")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function settingFilterAdvice(JoinPointInterface $joinPoint)
    {
        return 'pointcutExpressionSettingFilterOptionA on';
    }
}
