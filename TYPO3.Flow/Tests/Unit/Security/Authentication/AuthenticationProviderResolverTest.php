<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Object\ObjectManager;
use TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver;
use TYPO3\Flow\Security\Authentication\Provider\ValidShortName;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the security interceptor resolver
 */
class AuthenticationProviderResolverTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\NoAuthenticationProviderFoundException
     */
    public function resolveProviderObjectNameThrowsAnExceptionIfNoProviderIsAvailable()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);

        $providerResolver->resolveProviderClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveProviderReturnsTheCorrectProviderForAShortName()
    {
        $getCaseSensitiveObjectNameCallback = function () {
            $args = func_get_args();

            if ($args[0] === ValidShortName::class) {
                return ValidShortName::class;
            }

            return false;
        };

        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('ValidShortName');

        $this->assertEquals(ValidShortName::class, $providerClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveProviderReturnsTheCorrectProviderForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('existingProviderClass')->will($this->returnValue('existingProviderClass'));

        $providerResolver = new AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('existingProviderClass');

        $this->assertEquals('existingProviderClass', $providerClass, 'The wrong classname has been resolved');
    }
}
