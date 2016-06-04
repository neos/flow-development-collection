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

/**
 * Testcase for the security interceptor resolver
 *
 */
class AuthenticationProviderResolverTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException TYPO3\Flow\Security\Exception\NoAuthenticationProviderFoundException
     */
    public function resolveProviderObjectNameThrowsAnExceptionIfNoProviderIsAvailable()
    {
        $mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManager')->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $providerResolver = new \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver($mockObjectManager);

        $providerResolver->resolveProviderClass('notExistingClass');
    }

    /**
     * @test
     */
    public function resolveProviderReturnsTheCorrectProviderForAShortName()
    {
        $getCaseSensitiveObjectNameCallback = function () {
            $args = func_get_args();

            if ($args[0] === 'TYPO3\Flow\Security\Authentication\Provider\ValidShortName') {
                return 'TYPO3\Flow\Security\Authentication\Provider\ValidShortName';
            }

            return false;
        };

        $mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManager')->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));

        $providerResolver = new \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('ValidShortName');

        $this->assertEquals('TYPO3\Flow\Security\Authentication\Provider\ValidShortName', $providerClass, 'The wrong classname has been resolved');
    }

    /**
     * @test
     */
    public function resolveProviderReturnsTheCorrectProviderForACompleteClassName()
    {
        $mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManager')->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('existingProviderClass')->will($this->returnValue('existingProviderClass'));

        $providerResolver = new \TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver($mockObjectManager);
        $providerClass = $providerResolver->resolveProviderClass('existingProviderClass');

        $this->assertEquals('existingProviderClass', $providerClass, 'The wrong classname has been resolved');
    }
}
