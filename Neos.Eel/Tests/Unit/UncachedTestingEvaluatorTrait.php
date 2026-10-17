<?php

namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\StringFrontend;
use Neos\Eel\CompilingEvaluator;
use PHPUnit\Framework\MockObject\MockBuilder;

/** @method MockBuilder getMockBuilder(string $string) */
trait UncachedTestingEvaluatorTrait
{
    private function createTestingEelEvaluator(): CompilingEvaluator
    {
        $stringFrontendMock = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $stringFrontendMock->method('has')->willReturn(false);
        $stringFrontendMock->method('get')->willReturn(false);

        $compilingEvaluate = new CompilingEvaluator();
        $compilingEvaluate->injectExpressionCache($stringFrontendMock);
        return $compilingEvaluate;
    }
}
