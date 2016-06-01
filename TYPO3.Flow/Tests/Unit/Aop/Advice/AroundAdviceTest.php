<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Advice;

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
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint)->will($this->returnValue('result'));

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', false);
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
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('result'));

        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', false);
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
