<?php
namespace Neos\Flow\Tests\Unit\Aop\Advice;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop;

/**
 * Testcase for the Abstract Method Interceptor Builder
 */
class AroundAdviceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();

        $mockAspect = $this->createMock(Fixtures\SomeClass::class);
        $mockAspect->expects(self::once())->method('someMethod')->with($mockJoinPoint)->will(self::returnValue('result'));

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::once())->method('get')->with('aspectObjectName')->will(self::returnValue($mockAspect));

        $advice = new Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return true;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        self::assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }

    /**
     * @test
     * @return void
     */
    public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse()
    {
        $mockAdviceChain = $this->getMockBuilder(Aop\Advice\AdviceChain::class)->disableOriginalConstructor()->getMock();
        $mockAdviceChain->expects(self::once())->method('proceed')->will(self::returnValue('result'));

        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();
        $mockJoinPoint->expects(self::any())->method('getAdviceChain')->will(self::returnValue($mockAdviceChain));

        $mockAspect = $this->createMock(Fixtures\SomeClass::class);
        $mockAspect->expects(self::never())->method('someMethod');

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects(self::any())->method('get')->will(self::returnValue($mockAspect));

        $advice = new Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        self::assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }
}
