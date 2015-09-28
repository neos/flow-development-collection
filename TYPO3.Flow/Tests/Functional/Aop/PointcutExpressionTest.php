<?php
namespace TYPO3\Flow\Tests\Functional\Aop;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Test suite for poincut expression related features
 *
 */
class PointcutExpressionTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function settingFilterMatchesIfSpecifiedSettingIsEnabled()
    {
        $target = new Fixtures\PointcutExpressionTestingTarget();
        $this->assertSame('pointcutExpressionSettingFilterOptionA on', $target->testSettingFilter());
    }
}
