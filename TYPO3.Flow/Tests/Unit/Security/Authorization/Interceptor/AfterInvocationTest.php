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

use TYPO3\Flow\Tests\UnitTestCase;
use \TYPO3\Flow\Security;

/**
 * Testcase for the policy enforcement interceptor
 */
class AfterInvocationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invokeReturnsTheResultPreviouslySetBySetResultIfTheMethodIsNotIntercepted()
    {
        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockAfterInvocationManager = $this->createMock(Security\Authorization\AfterInvocationManagerInterface::class);

        $theResult = new \ArrayObject(['some' => 'stuff']);

        $interceptor = new Security\Authorization\Interceptor\AfterInvocation($mockSecurityContext, $mockAfterInvocationManager);
        $interceptor->setResult($theResult);
        $this->assertSame($theResult, $interceptor->invoke());
    }
}
