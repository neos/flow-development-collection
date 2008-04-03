<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the default AOP Pointcut implementation
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_PointcutTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_AOP_Framework
	 */
	protected $AOPFramework;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->AOPFramework = $this->componentManager->getComponent('F3_FLOW3_AOP_Framework');
		if(!$this->AOPFramework->isInitialized()) {
			$this->AOPFramework->initialize($this->componentManager->getComponentConfigurations());
		}
	}

	/**
	 * Checks that the "pointcutTestingTargetClasses" pointcut matches with the
	 * expected class- and method names
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function pointcutTestingTargetClassesWorks() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'pointcutTestingTargetClasses');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut didn\'t match target class 1 although it should!');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass2');
		$method = $class->getMethod('otherMethodOther');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut didn\'t match target class 2 although it should!');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass3');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut matched target class 3 although it shouldn\'t!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('setSomeProperty');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut matched the BasicClass although it shouldn\'t!');
	}

	/**
	 * Checks that the "otherPointcutTestingTargetClass" pointcut matches only the
	 * expected class- and method names
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function otherPointcutTestingTargetClassesWorks() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'otherPointcutTestingTargetClass');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut matched the F3_TestPackage_PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new ReflectionClass('F3_TestPackage_OtherPointcutTestingTargetClass');
		$method = $class->getMethod('justOneMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut didn\'t match F3_TestPackage_OtherPointcutTestingTargetClass although it should!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('setSomeProperty');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut matched the BasicClass although it shouldn\'t!');
	}

	/**
	 * Checks that the "bothPointcuts" pointcut matches only the
	 * expected class- and method names
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function bothPointcutsPointcutWorks() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'bothPointcuts');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut didn\'t match the F3_TestPackage_PointcutTestingTargetClass1 although it should!');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass2');
		$method = $class->getMethod('otherMethodOther');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut didn\'t match target class 2 although it should!');

		$class = new ReflectionClass('F3_TestPackage_OtherPointcutTestingTargetClass');
		$method = $class->getMethod('justOneMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut didn\'t match F3_TestPackage_OtherPointcutTestingTargetClass although it should!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('setSomeProperty');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut matched the BasicClass although it shouldn\'t!');
	}

	/**
	 * Checks that a method is matched correctly, even if a second method exists whose name starts exactly like the full
	 * name of the first method.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ambiguousMethodNamesAreMatchedCorrectly() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'otherMethodButNotOtherMethodOther');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass2');
		$method = $class->getMethod('otherMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The otherMethodButNotOtherMethodOther pointcut did not match the "otherMethod" method although it should!');

		$method = $class->getMethod('otherMethodOther');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The otherMethodButNotOtherMethodOther pointcut matched the "otherMethodOther" method although it shouldn\'t!');
	}

	/**
	 * Checks that pointcuts referring to each other creating circular reference
	 * are detected and an exception is thrown.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwsExceptionOnCircularReference() {
		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');

		try {
			$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'circularReferencePointcut');
			$pointcut->matches($class, $method, microtime());
		} catch (Exception $exception) {
			return;
		}
		$this->fail('No exception was thrown although the circular reference pointcut has been invoked.');
	}

	/**
	 * Checks that the within() designator in a pointcut expression only matches the expected classes
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function withinDesignatorBasicallyWorks() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'serviceLayerClasses');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The serviceLayerClasses pointcut matched the F3_TestPackage_PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new ReflectionClass('F3_TestPackage_ServiceLayerClass');
		$method = $class->getMethod('someMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The serviceLayerClasses pointcut didn\'t match the target class although it should!');
	}

	/**
	 * Checks that the within() designator in a pointcut expression works in combination with the method() designator
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function withinDesignatorWorksInCombinationWithMethod() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'basicClassOrServiceLayerClasses');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The basicClassOrServiceLayerClasses pointcut matched the F3_TestPackage_PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new ReflectionClass('F3_TestPackage_ServiceLayerClass');
		$method = $class->getMethod('someMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The basicClassOrServiceLayerClasses pointcut didn\'t match the service layer class although it should!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('setSomeProperty');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The basicClassOrServiceLayerClasses pointcut didn\'t match the basic class although it should!');
	}

	/**
	 * Checks if the visibility modifier (public | protected | private) is respected in pointcut expressions
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function visibilityModifierWorks() {
		$pointcut = $this->AOPFramework->findPointcut('F3_TestPackage_PointcutTestingAspect', 'publicMethodsOfBasicClass');

		$class = new ReflectionClass('F3_TestPackage_PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut matched the F3_TestPackage_PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('setSomeProperty');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut didn\'t match the basic class although it should!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('someProtectedMethod');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut matched a protected method although it shouldn\'t!');

		$class = new ReflectionClass('F3_TestPackage_BasicClass');
		$method = $class->getMethod('somePrivateMethod');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut matched a private method although it shouldn\'t!');
	}
}
?>