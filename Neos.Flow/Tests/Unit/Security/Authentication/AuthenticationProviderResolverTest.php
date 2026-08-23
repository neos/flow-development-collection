<?php

namespace Neos\Flow\Tests\Unit\Security\Authentication;

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
use Neos\Flow\Security\Authentication\AuthenticationProviderResolver;
use Neos\Flow\Security\Authentication\Provider\TestingProvider;
use Neos\Flow\Security\Exception\NoAuthenticationProviderFoundException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the security interceptor resolver
 */
class AuthenticationProviderResolverTest extends UnitTestCase
{
    #[Test]
    public function resolveProviderObjectNameThrowsAnExceptionIfNoProviderIsAvailable()
    {
        $this->expectException(NoAuthenticationProviderFoundException::class);
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->willReturn(false);

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);

        $providerResolver->resolveProviderClass('notExistingClass');
    }

    #[Test]
    public function resolveProviderReturnsTheCorrectProviderForAShortName()
    {
        $longClassNameForTest = TestingProvider::class;

        $getCaseSensitiveObjectNameCallback = function () use ($longClassNameForTest) {
            $args = func_get_args();

            if ($args[0] === $longClassNameForTest) {
                return $longClassNameForTest;
            }

            return false;
        };

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->willReturnCallback($getCaseSensitiveObjectNameCallback);

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('TestingProvider');

        self::assertEquals($longClassNameForTest, $providerClass, 'The wrong classname has been resolved');
    }

    #[Test]
    public function resolveProviderReturnsTheCorrectProviderForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->with(TestingProvider::class)->willReturn(TestingProvider::class);

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass(TestingProvider::class);

        self::assertEquals(TestingProvider::class, $providerClass, 'The wrong classname has been resolved');
    }
}
