<?php
namespace TYPO3\FLOW3\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect for testing different kinds of pointcut expressions
 *
 * @FLOW3\Aspect
 */
class PointcutExpressionTestingAspect {

	/**
	 *
	 * @FLOW3\Around("method(TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\PointcutExpressionTestingTarget->testSettingFilter()) && setting(TYPO3.FLOW3.tests.functional.aop.pointcutExpressionSettingFilterOptionA)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function settingFilterAdvice(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		return 'pointcutExpressionSettingFilterOptionA on';
	}
}
?>