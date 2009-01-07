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
 * @version $Id:\F3\FLOW3\AOP\PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the default AOP Pointcut implementation
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\AOP\PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\AOP\Framework
	 */
	protected $AOPFramework;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->AOPFramework = $this->objectManager->getObject('F3\FLOW3\AOP\Framework');
		if (!$this->AOPFramework->isInitialized()) {
			$this->AOPFramework->initialize($this->objectManager->getObjectConfigurations());
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'pointcutTestingTargetClasses');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut didn\'t match target class 1 although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass2');
		$method = $class->getMethod('otherMethodOther');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut didn\'t match target class 2 although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass3');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut matched target class 3 although it shouldn\'t!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'otherPointcutTestingTargetClass');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut matched the \F3\TestPackage\PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\OtherPointcutTestingTargetClass');
		$method = $class->getMethod('justOneMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The pointcutTestingTargetClasses pointcut didn\'t match \F3\TestPackage\OtherPointcutTestingTargetClass although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'bothPointcuts');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut didn\'t match the \F3\TestPackage\PointcutTestingTargetClass1 although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass2');
		$method = $class->getMethod('otherMethodOther');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut didn\'t match target class 2 although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\OtherPointcutTestingTargetClass');
		$method = $class->getMethod('justOneMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The bothPointcuts pointcut didn\'t match \F3\TestPackage\OtherPointcutTestingTargetClass although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'otherMethodButNotOtherMethodOther');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass2');
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
		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');

		try {
			$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'circularReferencePointcut');
			$pointcut->matches($class, $method, microtime());
		} catch (\Exception $exception) {
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'serviceLayerClasses');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The serviceLayerClasses pointcut matched the \F3\TestPackage\PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\ServiceLayerClass');
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'basicClassOrServiceLayerClasses');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The basicClassOrServiceLayerClasses pointcut matched the \F3\TestPackage\PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\ServiceLayerClass');
		$method = $class->getMethod('someMethod');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The basicClassOrServiceLayerClasses pointcut didn\'t match the service layer class although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
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
		$pointcut = $this->AOPFramework->findPointcut('F3\TestPackage\PointcutTestingAspect', 'publicMethodsOfBasicClass');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\PointcutTestingTargetClass1');
		$method = $class->getMethod('method1');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut matched the \F3\TestPackage\PointcutTestingTargetClass1 although it shouldn\'t!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
		$method = $class->getMethod('setSomeProperty');
		$this->assertTrue($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut didn\'t match the basic class although it should!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
		$method = $class->getMethod('someProtectedMethod');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut matched a protected method although it shouldn\'t!');

		$class = new \F3\FLOW3\Reflection\ClassReflection('F3\TestPackage\BasicClass');
		$method = $class->getMethod('somePrivateMethod');
		$this->assertFalse($pointcut->matches($class, $method, microtime()), 'The publicMethodsOfBasicClass pointcut matched a private method although it shouldn\'t!');
	}
}
?>