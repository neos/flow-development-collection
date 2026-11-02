<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Security;
use Neos\Flow\Security\Authorization\InterceptorResolver;
use Neos\Flow\Security\Exception\NoInterceptorFoundException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the security interceptor resolver
 */
final class InterceptorResolverTest extends UnitTestCase
{
    #[Test]
    public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable()
    {
        $this->expectException(NoInterceptorFoundException::class);
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->willReturn((false));

        $interceptorResolver = new InterceptorResolver($mockObjectManager);

        $interceptorResolver->resolveInterceptorClass('notExistingClass');
    }

    #[Test]
    public function resolveInterceptorReturnsTheCorrectInterceptorForAShortName()
    {
        $longClassNameForTest = 'Neos\Flow\Security\Authorization\Interceptor\ValidShortName';

        $getCaseSensitiveObjectNameCallback = function () use ($longClassNameForTest) {
            $args = func_get_args();

            if ($args[0] === $longClassNameForTest) {
                return $longClassNameForTest;
            }

            return false;
        };

        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->willReturnCallback($getCaseSensitiveObjectNameCallback);

        $interceptorResolver = new InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ValidShortName');

        self::assertEquals($longClassNameForTest, $interceptorClass, 'The wrong classname has been resolved');
    }

    #[Test]
    public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassName()
    {
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->with('ExistingInterceptorClass')->willReturn(('ExistingInterceptorClass'));

        $interceptorResolver = new InterceptorResolver($mockObjectManager);
        $interceptorClass = $interceptorResolver->resolveInterceptorClass('ExistingInterceptorClass');

        self::assertEquals('ExistingInterceptorClass', $interceptorClass, 'The wrong classname has been resolved');
    }
}
