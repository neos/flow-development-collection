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
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint)->will($this->returnValue('result'));

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

        $advice = new Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return true;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        $this->assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }

    /**
     * @test
     * @return void
     */
    public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse()
    {
        $mockAdviceChain = $this->getMockBuilder(Aop\Advice\AdviceChain::class)->disableOriginalConstructor()->getMock();
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('result'));

        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockAspect = $this->createMock(Fixtures\SomeClass::class);
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

        $advice = new Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        $this->assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }
}
