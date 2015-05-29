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
 * Testcase for the Pointcut Filter
 *
 */
class PointcutFilterTest extends \TYPO3\Flow\Tests\UnitTestCase {


	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Aop\Exception\UnknownPointcutException
	 */
	public function matchesThrowsAnExceptionIfTheSpecifiedPointcutDoesNotExist() {
		$className = 'Foo';
		$methodName = 'bar';
		$methodDeclaringClassName = 'Baz';
		$pointcutQueryIdentifier = 42;

		$mockProxyClassBuilder = $this->getMock('TYPO3\Flow\Aop\Builder\ProxyClassBuilder', array('findPointcut'), array(), '', FALSE);
		$mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(FALSE));

		$pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
		$pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
		$pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
	}

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenClassName() {
		$className = 'Foo';
		$methodName = 'bar';
		$methodDeclaringClassName = 'Baz';
		$pointcutQueryIdentifier = 42;

		$mockPointcut = $this->getMock('TYPO3\Flow\Aop\Pointcut\Pointcut', array('matches'), array(), '', FALSE);
		$mockPointcut->expects($this->once())->method('matches')->with($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)->will($this->returnValue('the result'));

		$mockProxyClassBuilder = $this->getMock('TYPO3\Flow\Aop\Builder\ProxyClassBuilder', array('findPointcut'), array(), '', FALSE);
		$mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

		$pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
		$pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
		$this->assertSame('the result', $pointcutFilter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
	}

	/**
	 * @test
	 */
	public function getRuntimeEvaluationsDefinitionReturnsTheDefinitionArrayFromThePointcut() {
		$mockPointcut = $this->getMock('TYPO3\Flow\Aop\Pointcut\Pointcut', array(), array(), '', FALSE);
		$mockPointcut->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('evaluations')));

		$mockProxyClassBuilder = $this->getMock('TYPO3\Flow\Aop\Builder\ProxyClassBuilder', array('findPointcut'), array(), '', FALSE);
		$mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

		$pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
		$pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
		$this->assertEquals(array('evaluations'), $pointcutFilter->getRuntimeEvaluationsDefinition(), 'Something different from an array was returned.');
	}

	/**
	 * @test
	 */
	public function getRuntimeEvaluationsDefinitionReturnsAnEmptyArrayIfThePointcutDoesNotExist() {
		$mockProxyClassBuilder = $this->getMock('TYPO3\Flow\Aop\Builder\ProxyClassBuilder', array('findPointcut'), array(), '', FALSE);
		$mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(FALSE));

		$pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
		$pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);
		$this->assertEquals(array(), $pointcutFilter->getRuntimeEvaluationsDefinition(), 'The definition array has not been returned as exptected.');
	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesAsksTheResolvedPointcutToReduce() {
		$mockPointcut = $this->getMock('TYPO3\Flow\Aop\Pointcut\Pointcut', array(), array(), '', FALSE);
		$mockPointcut->expects($this->once())->method('reduceTargetClassNames')->will($this->returnValue('someResult'));

		$mockProxyClassBuilder = $this->getMock('TYPO3\Flow\Aop\Builder\ProxyClassBuilder', array('findPointcut'), array(), '', FALSE);
		$mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue($mockPointcut));

		$pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
		$pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

		$this->assertEquals('someResult', $pointcutFilter->reduceTargetClassNames(new \TYPO3\Flow\Aop\Builder\ClassNameIndex()));
	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesReturnsTheInputClassNameIndexIfThePointcutCouldNotBeResolved() {
		$mockProxyClassBuilder = $this->getMock('TYPO3\Flow\Aop\Builder\ProxyClassBuilder', array('findPointcut'), array(), '', FALSE);
		$mockProxyClassBuilder->expects($this->once())->method('findPointcut')->with('Aspect', 'pointcut')->will($this->returnValue(FALSE));

		$pointcutFilter = new \TYPO3\Flow\Aop\Pointcut\PointcutFilter('Aspect', 'pointcut');
		$pointcutFilter->injectProxyClassBuilder($mockProxyClassBuilder);

		$inputClassNameIndex = new \TYPO3\Flow\Aop\Builder\ClassNameIndex();

		$this->assertSame($inputClassNameIndex, $pointcutFilter->reduceTargetClassNames($inputClassNameIndex));
	}
}
