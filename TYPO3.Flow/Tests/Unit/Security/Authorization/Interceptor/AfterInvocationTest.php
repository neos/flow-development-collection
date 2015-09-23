<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the policy enforcement interceptor
 *
 */
class AfterInvocationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function invokeReturnsTheResultPreviouslySetBySetResultIfTheMethodIsNotIntercepted()
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $mockAfterInvocationManager = $this->getMock('TYPO3\Flow\Security\Authorization\AfterInvocationManagerInterface');

        $theResult = new \ArrayObject(array('some' => 'stuff'));

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation($mockSecurityContext, $mockAfterInvocationManager);
        $interceptor->setResult($theResult);
        $this->assertSame($theResult, $interceptor->invoke());
    }
}
