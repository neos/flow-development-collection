<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization\Interceptor;

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
 * Testcase for the authentication required security interceptor
 *
 */
class RequireAuthenticationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function invokeCallsTheAuthenticationManagerToPerformAuthentication()
    {
        $authenticationManager = $this->createMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\RequireAuthentication($authenticationManager);
        $interceptor->invoke();
    }
}
