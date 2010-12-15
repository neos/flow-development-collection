<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\AOP;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once ('Fixtures/AspectClassWithAllAdviceTypes.php');
require_once ('Fixtures/InterfaceForIntroduction.php');

/**
 * Testcase for the AOP Framework class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FrameworkTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $accessibleFrameworkClassName;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Set up this testcase
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildAspectContainersReturnsAnArrayOfAspectContainersForThoseClassesWhichActuallyAreAspects() {
		$classNames = array('Foo', 'Bar', 'Baz');
		$container1 = $this->getMock('F3\FLOW3\AOP\AspectContainer', array(), array(), '', FALSE);
		$container2 = $this->getMock('F3\FLOW3\AOP\AspectContainer', array(), array(), '', FALSE);
		$expectedAspectContainers = array('Foo' => $container1, 'Baz' => $container2);

		$framework = $this->getAccessibleMock('F3\FLOW3\AOP\Framework', array('buildAspectContainer'), array(), '', FALSE);
		$framework->expects($this->at(0))->method('buildAspectContainer')->with('Foo')->will($this->returnValue($container1));
		$framework->expects($this->at(1))->method('buildAspectContainer')->with('Bar')->will($this->returnValue(FALSE));
		$framework->expects($this->at(2))->method('buildAspectContainer')->with('Baz')->will($this->returnValue($container2));

		$actualAspectContainers = $framework->_call('buildAspectContainers', $classNames);
		$this->assertSame($expectedAspectContainers, $actualAspectContainers);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildAspectContainerThrowsExceptionIfTheClassIsTaggedAsAspectButContainsNoAdvice() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('isClassReflected')->with('TestAspect')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->any())->method('isClassTaggedWith')->with('TestAspect', 'aspect')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->any())->method('getClassMethodNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));

		$framework = $this->getAccessibleMock('F3\FLOW3\AOP\Framework', array('dummy'), array(), '', FALSE, TRUE);
		$framework->injectReflectionService($mockReflectionService);

		$framework->_call('buildAspectContainer', 'TestAspect');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildAspectContainerDetectsAllSupportedKindsOfAdviceAndPointcutsAndIntroductions() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array('detectAvailableClassNames', 'loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->expects($this->once())->method('loadFromCache')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->once())->method('detectAvailableClassNames')->will($this->returnValue(array('F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes')));
		$mockReflectionService->initialize(array());

		$mockPointcutExpressionParser = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutExpressionParser', array('parse'), array(), '', FALSE);
		$mockPointcutExpressionParser->expects($this->any())->method('parse')->will($this->returnCallBack(array($this, 'pointcutFilterCompositeCallBack')));

		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallBack(array($this, 'objectManagerCallBack')));

		$framework = $this->getAccessibleMock('F3\FLOW3\AOP\Framework', array('dummy'), array(), '', TRUE, TRUE);
		$framework->injectObjectManager($this->mockObjectManager);
		$framework->injectReflectionService($mockReflectionService);
		$framework->injectPointcutExpressionParser($mockPointcutExpressionParser);

		$container = $framework->_call('buildAspectContainer', 'F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes');
		$this->assertType('F3\FLOW3\AOP\AspectContainer', $container);
		$this->assertSame('F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes', $container->getClassName());
		$advisors = $container->getAdvisors();
		$this->assertType('F3\FLOW3\AOP\Advice\AroundAdvice', $advisors[0]->getAdvice());
		$this->assertSame('fooAround', $advisors[0]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\Advice\BeforeAdvice', $advisors[1]->getAdvice());
		$this->assertSame('fooBefore', $advisors[1]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\Advice\AfterReturningAdvice', $advisors[2]->getAdvice());
		$this->assertSame('fooAfterReturning', $advisors[2]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\Advice\AfterThrowingAdvice', $advisors[3]->getAdvice());
		$this->assertSame('fooAfterThrowing', $advisors[3]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\Advice\AfterAdvice', $advisors[4]->getAdvice());
		$this->assertSame('fooAfter', $advisors[4]->getPointcut()->getPointcutExpression());

		$pointcuts = $container->getPointcuts();
		$this->assertTrue(count($pointcuts) === 1);
		$this->assertType('F3\FLOW3\AOP\Pointcut\Pointcut', $pointcuts[0]);
		$this->assertSame('fooPointcut', $pointcuts[0]->getPointcutExpression());

		$introductions = $container->getIntroductions();
		$this->assertTrue(count($introductions) === 1);
		$this->assertType('F3\FLOW3\AOP\Introduction', $introductions[0]);
		$this->assertSame('F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes', $introductions[0]->getDeclaringAspectClassName());
		$this->assertSame('F3\FLOW3\Tests\AOP\Fixture\InterfaceForIntroduction', $introductions[0]->getInterfaceName());
		$this->assertSame('ThePointcutExpression', $introductions[0]->getPointcut()->getPointcutExpression());
	}

	/**
	 * call back for buildAspectContainerDetectsAllSupportedKindsOfAdviceAndPointcutsAndIntroductions
	 * @return \F3\FLOW3\AOP\Pointcut\PointcutFilterComposite but only as a mock!
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function pointcutFilterCompositeCallBack() {
		return $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite');
	}

	/**
	 * call back for buildAspectContainerDetectsAllSupportedKindsOfAdviceAndPointcutsAndIntroductions
	 * @return object but only mocks except for \F3\FLOW3\AOP\Advisor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function objectManagerCallBack() {
		$arguments = array_merge(func_get_args(), array($this->mockObjectManager));
		$objectName = array_shift($arguments);
		switch ($objectName) {
			case 'F3\FLOW3\AOP\Advisor' :
				return new \F3\FLOW3\AOP\Advisor(current($arguments), next($arguments));
			case 'F3\FLOW3\AOP\Pointcut\Pointcut' :
			default :
				return $this->getMock($objectName, array('dummy'), $arguments, '', TRUE);
		}
	}

}
?>