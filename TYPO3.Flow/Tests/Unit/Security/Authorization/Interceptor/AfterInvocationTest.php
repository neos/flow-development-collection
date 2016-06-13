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
        $mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
        $mockAfterInvocationManager = $this->createMock('TYPO3\Flow\Security\Authorization\AfterInvocationManagerInterface');

        $theResult = new \ArrayObject(array('some' => 'stuff'));

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation($mockSecurityContext, $mockAfterInvocationManager);
        $interceptor->setResult($theResult);
        $this->assertSame($theResult, $interceptor->invoke());
    }
}
