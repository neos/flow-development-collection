<?php
namespace TYPO3\FLOW3\Tests\Functional\Aop;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test suite for poincut expression related features
 *
 */
class PointcutExpressionTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function settingFilterMatchesIfSpecifiedSettingIsEnabled() {
		$target = new Fixtures\PointcutExpressionTestingTarget();
		$this->assertSame('pointcutExpressionSettingFilterOptionA on', $target->testSettingFilter());
	}

}
?>