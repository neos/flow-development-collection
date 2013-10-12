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
class AroundAdviceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), TRUE)), array('someMethod'));
		$mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint)->will($this->returnValue('result'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

		$advice = new \TYPO3\Flow\Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) { if ($joinPoint !== NULL) return TRUE; });
		$result = $advice->invoke($mockJoinPoint);

		$this->assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
	}

	/**
	 * @test
	 */
	public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse() {
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue('result'));

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), TRUE)), array('someMethod'));
		$mockAspect->expects($this->never())->method('someMethod');

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

		$advice = new \TYPO3\Flow\Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) { if ($joinPoint !== NULL) return FALSE; });
		$result = $advice->invoke($mockJoinPoint);

		$this->assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
	}
}
