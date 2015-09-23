<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Advice;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Abstract Method Interceptor Builder
 *
 */
class AroundAdviceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class, array(), array(), '', false);

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint)->will($this->returnValue('result'));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

        $advice = new \TYPO3\Flow\Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
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
        $mockAdviceChain = $this->getMock(\TYPO3\Flow\Aop\Advice\AdviceChain::class, array(), array(), '', false);
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('result'));

        $mockJoinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class, array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

        $advice = new \TYPO3\Flow\Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        $this->assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }
}
