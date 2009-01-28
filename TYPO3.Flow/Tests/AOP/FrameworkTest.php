<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

require_once ('Fixture/AspectClassWithAllAdviceTypes.php');
require_once ('Fixture/InterfaceForIntroduction.php');

/**
 * Testcase for the AOP Framework class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FrameworkTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var string
	 */
	protected $accessibleFrameworkClassName;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $mockObjectFactory;

	/**
	 * Set up this testcase
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

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

		$framework = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Framework'), array('buildAspectContainer'), array(), '', FALSE);
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
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService->expects($this->any())->method('isClassReflected')->with('TestAspect')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->any())->method('isClassTaggedWith')->with('TestAspect', 'aspect')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->any())->method('getClassMethodNames')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->any())->method('getClassPropertyNames')->will($this->returnValue(array()));

		$framework = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Framework'), array('dummy'), array(), '', FALSE, TRUE);
		$framework->injectReflectionService($mockReflectionService);

		$framework->_call('buildAspectContainer', 'TestAspect');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildAspectContainerDetectsAllSupportedKindsOfAdviceAndPointcutsAndIntroductions() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array('F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes'));

		$testcase = $this;

		$pointcutFilterCompositeCallBack = function() use ($testcase) {
			return $testcase->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), func_get_args());
		};

		$mockPointcutExpressionParser = $this->getMock('F3\FLOW3\AOP\PointcutExpressionParser', array('parse'), array(), '', FALSE);
		$mockPointcutExpressionParser->expects($this->any())->method('parse')->will($this->returnCallBack($pointcutFilterCompositeCallBack, '__invoke'));

		$objectFactoryCallBack = function() use ($testcase) {
			$arguments = array_merge(func_get_args(), array($testcase->mockObjectManager));
			$objectName = array_shift($arguments);
			switch ($objectName) {
				case 'F3\FLOW3\AOP\Advisor' :
					return new \F3\FLOW3\AOP\Advisor(current($arguments), next($arguments));
				case 'F3\FLOW3\AOP\Pointcut' :
				default :
					return $testcase->getMock($objectName, array('dummy'), $arguments, '', TRUE);
			}
		};
		$this->mockObjectFactory->expects($this->any())->method('create')->will($this->returnCallBack($objectFactoryCallBack, '__invoke'));

		$framework = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\Framework'), array('dummy'), array($this->mockObjectManager, $this->mockObjectFactory), '', TRUE, TRUE);
		$framework->injectReflectionService($mockReflectionService);
		$framework->injectPointcutExpressionParser($mockPointcutExpressionParser);

		$container = $framework->_call('buildAspectContainer', 'F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes');
		$this->assertType('F3\FLOW3\AOP\AspectContainer', $container);
		$this->assertSame('F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes', $container->getClassName());
		$advisors = $container->getAdvisors();
		$this->assertType('F3\FLOW3\AOP\AroundAdvice', $advisors[0]->getAdvice());
		$this->assertSame('fooAround', $advisors[0]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\BeforeAdvice', $advisors[1]->getAdvice());
		$this->assertSame('fooBefore', $advisors[1]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\AfterReturningAdvice', $advisors[2]->getAdvice());
		$this->assertSame('fooAfterReturning', $advisors[2]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\AfterThrowingAdvice', $advisors[3]->getAdvice());
		$this->assertSame('fooAfterThrowing', $advisors[3]->getPointcut()->getPointcutExpression());
		$this->assertType('F3\FLOW3\AOP\AfterAdvice', $advisors[4]->getAdvice());
		$this->assertSame('fooAfter', $advisors[4]->getPointcut()->getPointcutExpression());

		$pointcuts = $container->getPointcuts();
		$this->assertTrue(count($pointcuts) === 1);
		$this->assertType('F3\FLOW3\AOP\Pointcut', $pointcuts[0]);
		$this->assertSame('fooPointcut', $pointcuts[0]->getPointcutExpression());

		$introductions = $container->getIntroductions();
		$this->assertTrue(count($introductions) === 1);
		$this->assertType('F3\FLOW3\AOP\Introduction', $introductions[0]);
		$this->assertSame('F3\FLOW3\Tests\AOP\Fixture\AspectClassWithAllAdviceTypes', $introductions[0]->getDeclaringAspectClassName());
		$this->assertSame('F3\FLOW3\Tests\AOP\Fixture\InterfaceForIntroduction', $introductions[0]->getInterfaceName());
		$this->assertSame('ThePointcutExpression', $introductions[0]->getPointcut()->getPointcutExpression());
	}
}
?>