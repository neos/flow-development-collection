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

/**
 * A target class for testing the AOP framework with never return type
 */
class TargetClassWithNeverReturnType
{
    public bool $beforeAdviceWasInvoked = false;
    public bool $afterThrowingAdviceWasInvoked = false;
    public bool $aroundAdviceWasInvoked = false;

    public function methodThatExits(): never
    {
        exit(42);
    }

    public function methodThatThrows(): never
    {
        throw new \RuntimeException('This method always throws', 1761036727);
    }

    public function aroundAdvicedMethodThatThrows(): never
    {
        throw new \RuntimeException('This method also always throws', 1761036724);
    }
}
