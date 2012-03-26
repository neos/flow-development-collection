<?php
namespace TYPO3\FLOW3\Tests\Unit\Aop\Advice;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
class AbstractAdviceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), TRUE)), array('someMethod'));
		$mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

		$advice = new \TYPO3\FLOW3\Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) { if ($joinPoint !== NULL) return TRUE; });
		$advice->invoke($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), TRUE)), array('someMethod'));
		$mockAspect->expects($this->never())->method('someMethod');

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

		$advice = new \TYPO3\FLOW3\Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) { if ($joinPoint !== NULL) return FALSE; });
		$advice->invoke($mockJoinPoint);
	}
}
?>