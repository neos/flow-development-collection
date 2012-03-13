<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Pointcut;

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
 * Testcase for the default AOP Pointcut implementation
 *
 */
class PointcutTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesChecksIfTheGivenClassAndMethodMatchThePointcutFilterComposite() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array('matches'), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('matches')->with($className, $methodName, $className, 1)->will($this->returnValue(TRUE));

		$pointcut = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		$this->assertTrue($pointcut->matches($className, $methodName, $className, 1));
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\AOP\Exception\CircularPointcutReferenceException
	 */
	public function matchesDetectsCircularMatchesAndThrowsAndException() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		for ($i = -1; $i <= \TYPO3\FLOW3\AOP\Pointcut\Pointcut::MAXIMUM_RECURSIONS; $i++) {
			$pointcut->matches($className, $methodName, $className,1);
		}
	}

	/**
	 * @test
	 */
	public function getPointcutExpressionReturnsThePointcutExpression() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		$this->assertSame($pointcutExpression, $pointcut->getPointcutExpression());
	}

	/**
	 * @test
	 */
	public function getAspectClassNameReturnsTheAspectClassName() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		$this->assertSame($aspectClassName, $pointcut->getAspectClassName());
	}

	/**
	 * @test
	 */
	public function getPointcutMethodNameReturnsThePointcutMethodName() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, 'PointcutMethod'), '', TRUE);
		$this->assertSame('PointcutMethod', $pointcut->getPointcutMethodName());
	}

	/**
	 * @test
	 */
	public function getRuntimeEvaluationsReturnsTheRuntimeEvaluationsDefinitionOfTheContainedPointcutFilterComposite() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('getRuntimeEvaluationsDefinition')->will($this->returnValue(array('runtimeEvaluationsDefinition')));

		$pointcut = new \TYPO3\FLOW3\AOP\Pointcut\Pointcut($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, $className);

		$this->assertEquals(array('runtimeEvaluationsDefinition'), $pointcut->getRuntimeEvaluationsDefinition(), 'The runtime evaluations definition has not been returned as expected.');
	}

	/**
	 * @test
	 */
	public function reduceTargetClassNamesAsksThePointcutsFilterCompositeToReduce() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';

		$targetClassNameIndex = new \TYPO3\FLOW3\AOP\Builder\ClassNameIndex();

		$mockPointcutFilterComposite = $this->getMock('TYPO3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('reduceTargetClassNames')->with($targetClassNameIndex)->will($this->returnValue('someResult'));

		$pointcut = new \TYPO3\FLOW3\AOP\Pointcut\Pointcut($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, $className);

		$this->assertEquals('someResult', $pointcut->reduceTargetClassNames($targetClassNameIndex));
	}
}
?>