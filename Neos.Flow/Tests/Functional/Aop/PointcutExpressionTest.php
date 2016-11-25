<?php
namespace Neos\Flow\Tests\Functional\Aop;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Test suite for poincut expression related features
 *
 */
class PointcutExpressionTest extends FunctionalTestCase
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
