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
use Neos\Flow\Security\Authentication\AuthenticationTokenResolver;
use Neos\Flow\Security\Exception\NoAuthenticationTokenFoundException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the security token resolver
 */
class AuthenticationTokenResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveTokenObjectNameThrowsAnExceptionIfNoProviderIsAvailable()
    {
        $this->expectException(NoAuthenticationTokenFoundException::class);
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnValue(false));

        $providerResolver = new AuthenticationTokenResolver($mockObjectManager);

        $providerResolver->resolveTokenClass('notExistingClass');
    }

    /**
     * @test
     */
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

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnCallBack($getCaseSensitiveObjectNameCallback));

        $providerResolver = new AuthenticationTokenResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveTokenClass('ValidShortName');

        self::assertEquals($longClassNameForTest, $providerClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveTokenReturnsTheCorrectTokenForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->with('existingTokenClass')->will(self::returnValue('existingTokenClass'));

        $providerResolver = new AuthenticationTokenResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveTokenClass('existingTokenClass');

        self::assertEquals('existingTokenClass', $providerClass, 'The wrong classname has been resolved');
    }
}
