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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Testcase for the AOP Proxy Class Builder
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\FrameworkTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProxyClassBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildProxyClassReturnsFalseIfNoMethodsWereInterceptedNorInterfacesIntroduced() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService->expects($this->any())->method('getClassMethodNames')->will($this->returnValue(array()));

		$builder = $this->getMock('F3\FLOW3\AOP\ProxyClassBuilder', array('dummy'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);
		$proxyBuildResult = $builder->buildProxyClass(__CLASS__, array(), 'Testing');
		$this->assertFalse($proxyBuildResult);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildProxyReturnsAllImportantBuildInformationCreatedByTheSubMethodInAnArray() {
		$targetClassName = 'ProxyClassBuilderTest';
		$aspectContainers = array();

		$expectedProxyClassName = 'ProxyClassBuilderTest_Proxy';
		$expectedAdvicedMethodsInformation = array('AdvicedMethodsInformation');
		$proxyClassTemplate = '###CLASS_ANNOTATIONS### ###PROXY_NAMESPACE### ###PROXY_CLASS_NAME### ###TARGET_CLASS_NAME### ###INTRODUCED_INTERFACES### ###METHODS_AND_ADVICES_ARRAY_CODE### ###METHODS_INTERCEPTOR_CODE###';
		$expectedProxyCode = "ClassAnnotations " . __NAMESPACE__ . " $expectedProxyClassName $targetClassName IntroducedInterfaces MethodsAndAdvicesArrayCode MethodsInterceptorCode";

		$mockIntroduction = $this->getMock('F3\FLOW3\AOP\Introduction', array(), array(), '', FALSE);

		$methodsToMock = array('getMatchingIntroductions', 'getInterfaceNamesFromIntroductions', 'getMethodsFromTargetClass', 'getIntroducedMethodsFromIntroductions', 'addConstructorToInterceptedMethods', 'addWakeupToInterceptedMethods', 'getAdvicedMethodsInformation', 'getProxyNamespace', 'renderProxyClassName', 'buildClassAnnotationsCode', 'buildIntroducedInterfacesCode', 'buildMethodsInterceptorCode', 'buildMethodsAndAdvicesArrayCode');
		$builder = $this->getMock('F3\FLOW3\AOP\ProxyClassBuilder', $methodsToMock, array(), '', FALSE);
		$builder->setProxyClassTemplate($proxyClassTemplate);
		$builder->expects($this->once())->method('getMatchingIntroductions')->with($aspectContainers, $targetClassName)->will($this->returnValue(array($mockIntroduction)));
		$builder->expects($this->once())->method('getMethodsFromTargetClass')->will($this->returnValue(array()));
		$builder->expects($this->once())->method('getInterfaceNamesFromIntroductions')->will($this->returnValue(array('class' => 'Foo', 'method' => 'Bar')));
		$builder->expects($this->once())->method('getIntroducedMethodsFromIntroductions')->will($this->returnValue(array(array('IntroducedInterface', 'introducedMethod'))));
		$builder->expects($this->once())->method('addConstructorToInterceptedMethods');
		$builder->expects($this->once())->method('addWakeupToInterceptedMethods');
		$builder->expects($this->once())->method('getAdvicedMethodsInformation')->will($this->returnValue($expectedAdvicedMethodsInformation));
		$builder->expects($this->once())->method('getProxyNamespace')->with($targetClassName)->will($this->returnValue(__NAMESPACE__));
		$builder->expects($this->once())->method('renderProxyClassName')->with($targetClassName, 'Testing')->will($this->returnValue($expectedProxyClassName));
		$builder->expects($this->once())->method('buildClassAnnotationsCode')->with($targetClassName)->will($this->returnValue('ClassAnnotations'));
		$builder->expects($this->once())->method('buildIntroducedInterfacesCode')->will($this->returnValue('IntroducedInterfaces'));
		$builder->expects($this->once())->method('buildMethodsInterceptorCode')->will($this->returnValue('MethodsInterceptorCode'));
		$builder->expects($this->once())->method('buildMethodsAndAdvicesArrayCode')->will($this->returnValue('MethodsAndAdvicesArrayCode'));


		$expectedProxyBuildResult = array(
			'proxyClassName' => __NAMESPACE__ . '\\' . $expectedProxyClassName,
			'proxyClassCode' => $expectedProxyCode,
			'advicedMethodsInformation' => $expectedAdvicedMethodsInformation
		);
		$actualProxyBuildResult = $builder->buildProxyClass($targetClassName, $aspectContainers, 'Testing');
		$this->assertSame($expectedProxyBuildResult, $actualProxyBuildResult);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodsFromTargetClassReturnsMethodNamesAndTheirDeclaringClassOfTheSpecifiedClassAndEvenIfTheyDontExistTheConstructAndWakeupMethod() {
		$className = uniqid('Foo');
		eval("class $className { public function foo() {} protected function bar() {} } ");

		$expectedMethodNames = array(
			array(NULL, '__construct'),
			array($className, 'foo'),
			array($className, 'bar'),
			array(NULL, '__wakeup')
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService->expects($this->any())->method('getClassMethodNames')->with($className)->will($this->returnValue(array('foo', 'bar')));

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);
		$actualMethodNames = $builder->_call('getMethodsFromTargetClass', $className);
		$this->assertSame($expectedMethodNames, $actualMethodNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildIntroducedInterfacesCodePreparesListOfInterfacesForProxyClassCode() {
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);

		$this->assertSame('', $builder->_call('buildIntroducedInterfacesCode', array()));
		$this->assertSame('Foo, ', $builder->_call('buildIntroducedInterfacesCode', array('Foo')));
		$this->assertSame('Foo, Bar, ', $builder->_call('buildIntroducedInterfacesCode', array('Foo', 'Bar')));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodsAndAdvicesArrayCodeConvertsTheMethodsAndAdvicesArrayIntoProperCode() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');

		$mockAdvice1 = $this->getMock('F3\FLOW3\AOP\AroundAdvice', array(), array('Aspect1', 'advice1', $mockObjectManager), '', TRUE);
		$mockAdvice1->expects($this->once())->method('getAspectObjectName')->will($this->returnValue('Aspect1'));
		$mockAdvice1->expects($this->once())->method('getAdviceMethodName')->will($this->returnValue('advice1'));
		$mockAdvice1ClassName = get_class($mockAdvice1);
		$mockAdvice2 = $this->getMock('F3\FLOW3\AOP\BeforeAdvice', array(), array('Aspect2', 'advice2', $mockObjectManager), '', TRUE);
		$mockAdvice2->expects($this->once())->method('getAspectObjectName')->will($this->returnValue('Aspect2'));
		$mockAdvice2->expects($this->once())->method('getAdviceMethodName')->will($this->returnValue('advice2'));
		$mockAdvice2ClassName = get_class($mockAdvice2);

		$methodsAndAdvices = array(
			'fooMethod' => array(
				'groupedAdvices' => array(
					get_class($mockAdvice1) => array($mockAdvice1),
					get_class($mockAdvice2) => array($mockAdvice2),
				)
			)
		);

		$expectedCode = "
		\$this->targetMethodsAndGroupedAdvices = array(
			'fooMethod' => array(
				'$mockAdvice1ClassName' => array(
					\$this->objectFactory->create('$mockAdvice1ClassName', 'Aspect1', 'advice1', \$this->objectManager),
				),
				'$mockAdvice2ClassName' => array(
					\$this->objectFactory->create('$mockAdvice2ClassName', 'Aspect2', 'advice2', \$this->objectManager),
				),
			),
		);" . chr(10);


		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$actualCode = $builder->_call('buildMethodsAndAdvicesArrayCode', $methodsAndAdvices);
		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodsInterceptorCodePreparesInterceptionCodeByCallingTheResponsibleInterceptorBuilder() {
		$mockEmptyConstructorInterceptorBuilder = $this->getMock('F3\FLOW3\AOP\EmptyConstructorInterceptorBuilder', array('build'), array(), '', FALSE);
		$mockEmptyConstructorInterceptorBuilder->expects($this->once())->method('build')->will($this->returnValue('EmptyConstructor '));
		$mockEmptyMethodInterceptorBuilder = $this->getMock('F3\FLOW3\AOP\EmptyMethodInterceptorBuilder', array('build'), array(), '', FALSE);
		$mockEmptyMethodInterceptorBuilder->expects($this->once())->method('build')->will($this->returnValue('EmptyMethod'));

		$mockAdvicedConstructorInterceptorBuilder = $this->getMock('F3\FLOW3\AOP\AdvicedConstructorInterceptorBuilder', array('build'), array(), '', FALSE);
		$mockAdvicedConstructorInterceptorBuilder->expects($this->once())->method('build')->will($this->returnValue('AdvicedConstructor '));
		$mockAdvicedMethodInterceptorBuilder = $this->getMock('F3\FLOW3\AOP\AdvicedMethodInterceptorBuilder', array('build'), array(), '', FALSE);
		$mockAdvicedMethodInterceptorBuilder->expects($this->once())->method('build')->will($this->returnValue('AdvicedMethod'));

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$builder->injectEmptyConstructorInterceptorBuilder($mockEmptyConstructorInterceptorBuilder);
		$builder->injectEmptyMethodInterceptorBuilder($mockEmptyMethodInterceptorBuilder);
		$builder->injectAdvicedConstructorInterceptorBuilder($mockAdvicedConstructorInterceptorBuilder);
		$builder->injectAdvicedMethodInterceptorBuilder($mockAdvicedMethodInterceptorBuilder);

		$interceptedMethods = array(
			'__construct' => array('groupedAdvices' => array()),
			'foo' => array('groupedAdvices' => array())
		);

		$actualCode = $builder->_call('buildMethodsInterceptorCode', $interceptedMethods, 'Foo');
		$this->assertSame('EmptyConstructor EmptyMethod', $actualCode);

		$interceptedMethods = array(
			'__construct' => array('groupedAdvices' => array('mockAdvice')),
			'foo' => array('groupedAdvices' => array('mockAdvice'))
		);

		$actualCode = $builder->_call('buildMethodsInterceptorCode', $interceptedMethods, 'Foo');
		$this->assertSame('AdvicedConstructor AdvicedMethod', $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addAdvicedMethodsToInterceptedMethodsTraversesAspectsAndAdvisorsToCompileAdvicesAndInterceptedMethods() {

		$targetClassName = 'TargetClass';
		$methods = array(array('Foo', 'foo'), array('Bar', 'bar'));

		$mockPointcut1 = $this->getMock('F3\FLOW3\AOP\Pointcut', array('matches'), array(), '', FALSE);
		$mockPointcut1->expects($this->at(0))->method('matches')->with('TargetClass', 'foo')->will($this->returnValue(FALSE));
		$mockPointcut1->expects($this->at(1))->method('matches')->with('TargetClass', 'bar')->will($this->returnValue(TRUE));

		$mockAdvice1 = $this->getMock('F3\FLOW3\AOP\AroundAdvice', array('dummy'), array(), '', FALSE);

		$mockAdvisor1 = $this->getMock('F3\FLOW3\AOP\Advisor', array('getPointcut', 'getAdvice'), array(), '', FALSE);
		$mockAdvisor1->expects($this->once())->method('getPointcut')->will($this->returnValue($mockPointcut1));
		$mockAdvisor1->expects($this->once())->method('getAdvice')->will($this->returnValue($mockAdvice1));

		$mockAspectContainer1 = $this->getMock('F3\FLOW3\AOP\AspectContainer', array('getAdvisors'), array(), '', FALSE);
		$mockAspectContainer1->expects($this->once())->method('getAdvisors')->will($this->returnValue(array($mockAdvisor1)));

		$aspectContainers = array($mockAspectContainer1);
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$actualInterceptedMethods = array();
		$builder->_callRef('addAdvicedMethodsToInterceptedMethods', $actualInterceptedMethods, $methods, $targetClassName, $aspectContainers);

		$expectedInterceptedMethods = array(
			'bar' => array(
				'groupedAdvices' => array(
					get_class($mockAdvice1) => array($mockAdvice1),
				),
				'declaringClassName' => 'Bar'
			)
		);
		$this->assertSame($expectedInterceptedMethods, $actualInterceptedMethods);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addIntroducedMethodsToInterceptedMethodsAddsSpecifiedMethodsToTheInterceptedMethodsListIfItIsNotAlreadyThere() {
		$targetClassName = 'TargetClass';
		$methods = array(array('Foo', 'foo'), array('Bar', 'bar'));
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);

		$actualInterceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('x'),
				'declaringClassName' => 'Foo'
			)
		);
		$builder->_callRef('addIntroducedMethodsToInterceptedMethods', $actualInterceptedMethods, $methods);

		$expectedInterceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('x'),
				'declaringClassName' => 'Foo'
			),
			'bar' => array(
				'groupedAdvices' => array(),
				'declaringClassName' => 'Bar'
			)
		);
		$this->assertSame($expectedInterceptedMethods, $actualInterceptedMethods);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function  addConstructorToInterceptedMethodsAssuresThatTheConstructorIsInTheListOfInterceptedMethods() {
		$targetClassName = uniqid('TargetClass');
		eval("class $targetClassName { public function __construct() {} }");

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);

		$actualInterceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('x'),
				'declaringClassName' => 'Foo'
			)
		);
		$builder->_callRef('addConstructorToInterceptedMethods', $actualInterceptedMethods, $targetClassName);

		$expectedInterceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('x'),
				'declaringClassName' => 'Foo'
			),
			'__construct' => array(
				'groupedAdvices' => array(),
				'declaringClassName' => $targetClassName
			)
		);
		$this->assertSame($expectedInterceptedMethods, $actualInterceptedMethods);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function  addWakeupToInterceptedMethodsAssuresThatAWakeupMethodIsInTheListOfInterceptedMethods() {
		$targetClassName = uniqid('TargetClass');
		eval("class $targetClassName { }");

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);

		$actualInterceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('x'),
				'declaringClassName' => 'Foo'
			)
		);
		$builder->_callRef('addWakeupToInterceptedMethods', $actualInterceptedMethods, $targetClassName);

		$expectedInterceptedMethods = array(
			'foo' => array(
				'groupedAdvices' => array('x'),
				'declaringClassName' => 'Foo'
			),
			'__wakeup' => array(
				'groupedAdvices' => array(),
				'declaringClassName' => NULL
			)
		);
		$this->assertSame($expectedInterceptedMethods, $actualInterceptedMethods);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMatchingIntroductionsReturnsAnArrayOfAllMatchingIntroductionsForTheGivenTargetClass() {
		$targetClassName = 'TargetClass';

		$mockAspectContainer1 = $this->getMock('F3\FLOW3\AOP\AspectContainer', array('getIntroductions'), array(), '', FALSE);
		$mockAspectContainer1->expects($this->once())->method('getIntroductions')->will($this->returnValue(array()));

		$mockPointcut1 = $this->getMock('F3\FLOW3\AOP\Pointcut', array('matches'), array(), '', FALSE);
		$mockPointcut1->expects($this->once())->method('matches')->will($this->returnValue(FALSE));

		$mockPointcut2 = $this->getMock('F3\FLOW3\AOP\Pointcut', array('matches'), array(), '', FALSE);
		$mockPointcut2->expects($this->once())->method('matches')->with($targetClassName, NULL)->will($this->returnValue(TRUE));

		$mockIntroduction1 = $this->getMock('F3\FLOW3\AOP\Introduction', array('getPointcut'), array(), '', FALSE);
		$mockIntroduction1->expects($this->once())->method('getPointcut')->will($this->returnValue($mockPointcut1));

		$mockIntroduction2 = $this->getMock('F3\FLOW3\AOP\Introduction', array('getPointcut'), array(), '', FALSE);
		$mockIntroduction2->expects($this->once())->method('getPointcut')->will($this->returnValue($mockPointcut2));

		$mockIntroductions = array($mockIntroduction1, $mockIntroduction2);

		$mockAspectContainer2 = $this->getMock('F3\FLOW3\AOP\AspectContainer', array('getIntroductions'), array(), '', FALSE);
		$mockAspectContainer2->expects($this->once())->method('getIntroductions')->will($this->returnValue($mockIntroductions));

		$aspectContainers = array($mockAspectContainer1, $mockAspectContainer2);

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$actualMatchingIntroductions = $builder->_call('getMatchingIntroductions', $aspectContainers, $targetClassName);
		$this->assertSame(array($mockIntroduction2), $actualMatchingIntroductions);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfaceNamesFromIntroductionsReturnsInterfaceNamesContainedInIntroductions() {
		$mockIntroduction1 = $this->getMock('F3\FLOW3\AOP\Introduction', array('getInterfaceName'), array(), '', FALSE);
		$mockIntroduction1->expects($this->once())->method('getInterfaceName')->will($this->returnValue('Foo'));

		$mockIntroduction2 = $this->getMock('F3\FLOW3\AOP\Introduction', array('getInterfaceName'), array(), '', FALSE);
		$mockIntroduction2->expects($this->once())->method('getInterfaceName')->will($this->returnValue('Bar'));

		$mockIntroductions = array($mockIntroduction1, $mockIntroduction2);
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$actualInterfaceNames = $builder->_call('getInterfaceNamesFromIntroductions', $mockIntroductions);

		$this->assertSame(array('\Foo', '\Bar'), $actualInterfaceNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIntroducedMethodsFromIntroductionsReturnsArrayOfMethodInformationOfAllMethodsDeclaredByIntroducedInterfaces() {
		$mockIntroduction1 = $this->getMock('F3\FLOW3\AOP\Introduction', array('getInterfaceName'), array(), '', FALSE);
		$mockIntroduction1->expects($this->once())->method('getInterfaceName')->will($this->returnValue('Foo'));

		$mockIntroduction2 = $this->getMock('F3\FLOW3\AOP\Introduction', array('getInterfaceName'), array(), '', FALSE);
		$mockIntroduction2->expects($this->once())->method('getInterfaceName')->will($this->returnValue('Bar'));

		$mockIntroductions = array($mockIntroduction1, $mockIntroduction2);
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$actualInterfaceNames = $builder->_call('getInterfaceNamesFromIntroductions', $mockIntroductions);

		$this->assertSame(array('\Foo', '\Bar'), $actualInterfaceNames);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvicedMethodsInformationCompilesInformationAboutWhichAdvicesHaveBeenWovenIntoWhichMethods() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');

		$mockAdvice1 = $this->getMock('F3\FLOW3\AOP\AroundAdvice', array(), array('Aspect1', 'advice1', $mockObjectManager), '', TRUE);
		$mockAdvice1->expects($this->once())->method('getAspectObjectName')->will($this->returnValue('Aspect1'));
		$mockAdvice1->expects($this->once())->method('getAdviceMethodName')->will($this->returnValue('advice1'));
		$mockAdvice1ClassName = get_class($mockAdvice1);
		$mockAdvice2 = $this->getMock('F3\FLOW3\AOP\BeforeAdvice', array(), array('Aspect2', 'advice2', $mockObjectManager), '', TRUE);
		$mockAdvice2->expects($this->once())->method('getAspectObjectName')->will($this->returnValue('Aspect2'));
		$mockAdvice2->expects($this->once())->method('getAdviceMethodName')->will($this->returnValue('advice2'));
		$mockAdvice2ClassName = get_class($mockAdvice2);

		$interceptedMethods = array(
			'fooMethod' => array(
				'groupedAdvices' => array(
					get_class($mockAdvice1) => array($mockAdvice1),
					get_class($mockAdvice2) => array($mockAdvice2),
				)
			)
		);

		$expectedInformation = array(
			'fooMethod' => array(
				get_class($mockAdvice1) => array (
					array(
						'aspectObjectName' => 'Aspect1',
						'adviceMethodName' => 'advice1'
					)
				),
				get_class($mockAdvice2) => array (
					array(
						'aspectObjectName' => 'Aspect2',
						'adviceMethodName' => 'advice2'
					)
				),
			)
		);

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$actualInformation = $builder->_call('getAdvicedMethodsInformation', $interceptedMethods);
		$this->assertSame($expectedInformation, $actualInformation);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildClassAnnotationsCodeCreatesReadyToInsertCodeContainingAnnotationsOfTheGivenClass() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService->expects($this->any())->method('getClassTagsValues')->with('TestClass')->will($this->returnValue(array('foo' => array('bar', 'baz'), 'author' => array('me'))));

		$expectedCode = ' * @foo bar baz' . chr(10) .' * @author me' . chr(10);

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);
		$actualCode = $builder->_call('buildClassAnnotationsCode', 'TestClass');

		$this->assertSame($expectedCode, $actualCode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderProxyClassNameRendersAUniqueClassNameEvenIfAProxyClassNameForThatTargetClassAlreadyExists() {
		$className = uniqid('TestClass');
		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);

		$actualClassName = $builder->_call('renderProxyClassName', $className, 'Testing');
		$this->assertSame($className . '_AOPProxy_Testing', $actualClassName);

		$this->getMock($actualClassName, array(), array());
		$actualClassName = $builder->_call('renderProxyClassName', $className, 'Testing');
		$this->assertSame($className . '_AOPProxy_Testing_v2', $actualClassName);

		$this->getMock($actualClassName, array(), array());
		$actualClassName = $builder->_call('renderProxyClassName', $className, 'Testing');
		$this->assertSame($className . '_AOPProxy_Testing_v3', $actualClassName);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProxyNameSpaceExtractsTheNamespaceOfTheGivenFullQualifiedClassName() {

		$builder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\ProxyClassBuilder'), array('dummy'), array(), '', FALSE);
		$namespace = $builder->_call('getProxyNameSpace', __CLASS__);
		$this->assertSame(__NAMESPACE__, $namespace);
	}
}
?>