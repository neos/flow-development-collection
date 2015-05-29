<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Pointcut;

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
 * Testcase for the Pointcut Method Name Filter
 *
 */
class PointcutMethodNameFilterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesIgnoresFinalMethodsEvenIfTheirNameMatches() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				final public function someFinalMethod() {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array('isMethodFinal'), array(), '', FALSE);
		$mockReflectionService->expects($this->atLeastOnce())->method('isMethodFinal')->with($className, 'someFinalMethod')->will($this->returnValue(TRUE));

		$methodNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter('someFinalMethod');
		$methodNameFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($methodNameFilter->matches($className, 'someFinalMethod', $className, 1));
	}

	/**
	 * @test
	 */
	public function matchesTakesTheVisibilityModifierIntoAccountIfOneWasSpecified() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				public function somePublicMethod() {}
				protected function someProtectedMethod() {}
				private function somePrivateMethod() {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->atLeastOnce())->method('isMethodPublic')->will($this->onConsecutiveCalls(TRUE, FALSE, FALSE, TRUE));
		$mockReflectionService->expects($this->atLeastOnce())->method('isMethodProtected')->will($this->onConsecutiveCalls(FALSE, TRUE, FALSE, FALSE));
		$mockReflectionService->expects($this->atLeastOnce())->method('isMethodFinal')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue(array()));

		$methodNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter('some.*', 'public');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', NULL, 1));

		$methodNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter('some.*', 'protected');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', NULL, 1));
	}

	/**
	 * @test
	 */
	public function matchesChecksTheAvailablityOfAnArgumentNameIfArgumentConstraintsHaveBeenConfigured() {
		$className = 'TestClass' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $className {
				public function somePublicMethod(\$arg1) {}
				public function someOtherPublicMethod(\$arg1, \$arg2 = 'default') {}
				public function someThirdMethod(\$arg1, \$arg2, \$arg3 = 'default') {}
			}"
		);

		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->exactly(3))->method('getMethodParameters')->will($this->onConsecutiveCalls(
				array('arg1' => array()),
				array('arg1' => array(), 'arg2' => array()),
				array('arg1' => array(), 'arg2' => array(), 'arg3' => array())
		));

		$mockSystemLogger = $this->getMock('TYPO3\Flow\Log\Logger');
		$mockSystemLogger->expects($this->once())->method('log')->with($this->equalTo(
			'The argument "arg2" declared in pointcut does not exist in method ' . $className . '->somePublicMethod'
		));

		$argumentConstraints = array(
			'arg1' => array(
				'operator' => '==',
				'value' => 'someValue'
			),
			'arg2.some.sub.object' => array(
				'operator' => '==',
				'value' => 'someValue'
			)
		);

		$methodNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter('some.*', null, $argumentConstraints);
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$methodNameFilter->injectSystemLogger($mockSystemLogger);

		$methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1);

		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someOtherPublicMethod', $className, 1));
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someThirdMethod', $className, 1));
	}

	/**
	 * @test
	 */
	public function getRuntimeEvaluationsReturnsTheMethodArgumentConstraintsDefinitions() {
		$argumentConstraints = array(
			'arg2' => array(
				'operator' => '==',
				'value' => 'someValue'
			)
		);

		$expectedRuntimeEvaluations = array(
			'methodArgumentConstraints' => $argumentConstraints
		);

		$methodNameFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutMethodNameFilter('some.*', null, $argumentConstraints);

		$this->assertEquals($expectedRuntimeEvaluations, $methodNameFilter->getRuntimeEvaluationsDefinition(), 'The argument constraint definitions have not been returned as expected.');
	}
}
