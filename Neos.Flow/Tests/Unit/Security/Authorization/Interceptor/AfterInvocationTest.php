<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Authorization\AfterInvocationManagerInterface;
use Neos\Flow\Security\Authorization\Interceptor\AfterInvocation;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the policy enforcement interceptor
 */
final class AfterInvocationTest extends UnitTestCase
{
    #[Test]
    public function invokeReturnsTheResultPreviouslySetBySetResultIfTheMethodIsNotIntercepted()
    {
        $mockSecurityContext = $this->createStub(Context::class);
        $mockAfterInvocationManager = $this->createStub(AfterInvocationManagerInterface::class);

        $theResult = new \ArrayObject(['some' => 'stuff']);

        $interceptor = new AfterInvocation($mockSecurityContext, $mockAfterInvocationManager);
        $interceptor->setResult($theResult);
        self::assertSame($theResult, $interceptor->invoke());
    }
}
