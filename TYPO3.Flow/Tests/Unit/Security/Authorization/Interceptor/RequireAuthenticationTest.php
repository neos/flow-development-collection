<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
        $authenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\RequireAuthentication($authenticationManager);
        $interceptor->invoke();
    }
}
