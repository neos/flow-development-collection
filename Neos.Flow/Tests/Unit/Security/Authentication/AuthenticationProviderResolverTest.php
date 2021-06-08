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
use Neos\Flow\Security\Exception\NoAuthenticationProviderFoundException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the security interceptor resolver
 */
class AuthenticationProviderResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveProviderObjectNameThrowsAnExceptionIfNoProviderIsAvailable()
    {
        $this->expectException(NoAuthenticationProviderFoundException::class);
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnValue(false));

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);

        $providerResolver->resolveProviderClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveProviderReturnsTheCorrectProviderForAShortName()
    {
        $longClassNameForTest = 'Neos\Flow\Security\Authentication\Provider\ValidShortName';

        $getCaseSensitiveObjectNameCallback = function () use ($longClassNameForTest) {
            $args = func_get_args();

            if ($args[0] === $longClassNameForTest) {
                return $longClassNameForTest;
            }

            return false;
        };

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnCallBack($getCaseSensitiveObjectNameCallback));

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('ValidShortName');

        self::assertEquals($longClassNameForTest, $providerClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveProviderReturnsTheCorrectProviderForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->with('existingProviderClass')->will(self::returnValue('existingProviderClass'));

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('existingProviderClass');

        self::assertEquals('existingProviderClass', $providerClass, 'The wrong classname has been resolved');
    }
}
