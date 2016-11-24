<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * An aspect for testing different kinds of pointcut expressions
 *
 * @Flow\Aspect
 */
class PointcutExpressionTestingAspect
{
    /**
     *
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\PointcutExpressionTestingTarget->testSettingFilter()) && setting(Neos.Flow.tests.functional.aop.pointcutExpressionSettingFilterOptionA)")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function settingFilterAdvice(JoinPointInterface $joinPoint)
    {
        return 'pointcutExpressionSettingFilterOptionA on';
    }
}
