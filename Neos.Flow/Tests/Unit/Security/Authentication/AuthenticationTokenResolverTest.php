<?php

declare(strict_types=1);

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
use Neos\Flow\Security\Authentication\AuthenticationTokenResolver;
use Neos\Flow\Security\Exception\NoAuthenticationTokenFoundException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the security token resolver
 */
final class AuthenticationTokenResolverTest extends UnitTestCase
{
    #[Test]
    public function resolveTokenObjectNameThrowsAnExceptionIfNoProviderIsAvailable()
    {
        $this->expectException(NoAuthenticationTokenFoundException::class);
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->willReturn((false));

        $providerResolver = new AuthenticationTokenResolver($mockObjectManager);

        $providerResolver->resolveTokenClass('notExistingClass');
    }

    #[Test]
    public function resolveTokenReturnsTheCorrectTokenForAShortName()
    {
        $longClassNameForTest = 'Neos\Flow\Security\Authentication\Token\ValidShortName';

        $getCaseSensitiveObjectNameCallback = function () use ($longClassNameForTest) {
            $args = func_get_args();

            if ($args[0] === $longClassNameForTest) {
                return $longClassNameForTest;
            }

            return false;
        };

        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->willReturnCallback($getCaseSensitiveObjectNameCallback);

        $providerResolver = new AuthenticationTokenResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveTokenClass('ValidShortName');

        self::assertSame($longClassNameForTest, $providerClass, 'The wrong classname has been resolved');
    }

    #[Test]
    public function resolveTokenReturnsTheCorrectTokenForACompleteClassName()
    {
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->method('getClassNameByObjectName')->with('existingTokenClass')->willReturn(('existingTokenClass'));

        $providerResolver = new AuthenticationTokenResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveTokenClass('existingTokenClass');

        self::assertSame('existingTokenClass', $providerClass, 'The wrong classname has been resolved');
    }
}
