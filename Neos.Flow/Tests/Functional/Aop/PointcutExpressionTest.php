<?php

declare(strict_types=1);

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
use Neos\Flow\Tests\Functional\Aop\Fixtures\PointcutExpressionTestingTarget;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for pointcut expression related features
 *
 */
final class PointcutExpressionTest extends FunctionalTestCase
{
    #[Test]
    public function settingFilterMatchesIfSpecifiedSettingIsEnabled()
    {
        $target = new PointcutExpressionTestingTarget();
        self::assertSame('pointcutExpressionSettingFilterOptionA on', $target->testSettingFilter());
    }
}
