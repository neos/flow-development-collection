<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
     * @return string
     */
    public function settingFilterAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        return 'pointcutExpressionSettingFilterOptionA on';
    }
}
