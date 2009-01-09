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

/**
 * Testcase for the AOP Framework class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class FrameworkTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if constructor parameters still work after an object class has been proxied.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorsOfProxiedClassesAreStillIntact() {
		$objectWithConstructor = $this->objectManager->getObject('F3\TestPackage\ClassWithOptionalArguments', 'modified argument1', 'modified argument2', 'modified argument3');
		$this->assertEquals('modified argument1', $objectWithConstructor->argument1, 'The property set through the first constructor argument does not contain the expected value.');
		$this->assertEquals('modified argument2', $objectWithConstructor->argument2, 'The property set through the second constructor argument does not contain the expected value.');
		$this->assertEquals('modified argument3', $objectWithConstructor->argument3, 'The property set through the third constructor argument does not contain the expected value.');
	}

	/**
	 * Checks if the "chinese advice" is active when calling the getSomeProperty method of the BasicClass
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aroundAdviceBasicallyworks() {
		$basicObject = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$this->assertType('F3\FLOW3\AOP\ProxyInterface', $basicObject, 'The basic object seems not to be a proxy object!');
		$this->assertEquals('四十二', $basicObject->getSomeProperty(), 'The chinese advice seems not to be active - getSomeProperty() did not return the expected result.');
		$basicObject->setSomeProperty(100);
		$this->assertEquals(100, $basicObject->getSomeProperty(), 'The chinese advice intercepts the getSomeProperty() method although the property is not 42.');
	}

	/**
	 * Checks if a before advice basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function beforeAdviceBasicallyWorks() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\GetSomeChinesePropertyAspect');
		$time = 'before' . microtime();
		$target = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$target->setSomeProperty($time);
		$this->assertEquals($time, $aspect->getFlags('before'), 'The internal flag of the aspect did not contain the expected value after testing the before advice.');
	}

	/**
	 * Checks if an after returning advice basically works. Note that the after returning is triggered on the getSomeProperty() method (that's why we call it)
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function afterReturningAdviceBasicallyWorks() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\GetSomeChinesePropertyAspect');
		$time = 'afterReturning' . microtime();
		$target = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$target->setSomeProperty($time);
		$target->getSomeProperty();
		$this->assertEquals($time, $aspect->getFlags('afterReturning'), 'The internal flag of the aspect did not contain the expected value after testing the After Returning advice.');
	}

	/**
	 * Checks if an after returning advice works even on constructors for classes not having a constructor.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function afterReturningAdviceOnConstructorWorksEvenIfTargetClassHasNoConstructor() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\AfterNonExistingConstructorAspect');
		$this->objectManager->getObject('F3\TestPackage\BasicClass');
		$this->assertTrue($aspect->getFlags('afterReturning'), 'The internal flag of the aspect did not contain the expected value after testing the constructor advice.');
	}

	/**
	 * Checks if an after returning advice on __wakeup works even on classes not having __wakeup.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function afterReturningAdviceOnWakeupWorksEvenIfTargetClassHasNoWakeup() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\AfterNonExistingWakeupAspect');
		$target = $this->objectManager->getObject('F3\TestPackage\EmptyClass');
		$GLOBALS['reconstituteObject']['objectFactory'] = $this->objectFactory;
		$GLOBALS['reconstituteObject']['objectManager'] = $this->objectManager;
		$GLOBALS['reconstituteObject']['properties'] = array();
		$target = unserialize(serialize($target));
		unset($GLOBALS['reconstituteObject']);
		$this->assertTrue($aspect->getFlags('afterReturning'), 'The internal flag of the aspect did not contain the expected value after testing the wakeup advice.');
	}

	/**
	 * Checks if an after returning advice is not executed if an exception was thrown
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function afterReturningAdviceIsNotExecutedIfAnExceptionWasThrown() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\GetSomeChinesePropertyAspect');
		$time = 'afterReturning' . microtime();
		$target = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		try {
			$target->throwAnException('RuntimeException', $time);
		} catch (\Exception $exception) {
		}
		$this->assertNotEquals($time, $aspect->getFlags('afterReturning'), 'The After Returning Advice has been executed, although an exception was thrown.');
	}

	/**
	 * Checks if an after throwing advice basically works.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function afterThrowingAdviceBasicallyWorks() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\GetSomeChinesePropertyAspect');
		$time = 'afterThrowing' . microtime();
		$target = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		try {
			$target->throwAnException('RuntimeException', $time);
		} catch (\Exception $exception) {
		}
		$this->assertEquals($time, $aspect->getFlags('afterThrowing'), 'The internal flag of the aspect did not contain the expected value after testing the After Throwing advice.');
	}

	/**
	 * Checks if an after advice basically works.
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function afterAdviceBasicallyWorks() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\GetSomeChinesePropertyAspect');
		$time = 'after' . microtime();
		$target = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$target->setSomeProperty($time);
		$target->getSomeProperty();
		$this->assertEquals($time, $aspect->getFlags('after'), 'The internal flag of the aspect did not contain the expected value after testing the After advice.');
	}

	/**
	 * Checks if an after advice works if an exception was thrown.
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function afterAdviceWorksIfAnExceptionWasThrown() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\GetSomeChinesePropertyAspect');
		$time = 'after' . microtime();
		$target = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		try {
			$target->throwAnException('RuntimeException', $time);
		} catch (\Exception $exception) {
		}
		$this->assertEquals($time, $aspect->getFlags('after'), 'The internal flag of the aspect did not contain the expected value after testing the After advice.');
	}

	/**
	 * Checks if an introduction declaration basically works.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function introductionDeclarationBasicallyWorks() {
		$aspect = $this->objectManager->getObject('F3\TestPackage\IntroductionAspect');
		$target = $this->objectManager->getObject('F3\TestPackage\IntroductionTargetClass');
		$this->assertTrue(method_exists($target, 'newMethod'), 'The method "newMethod" does not exist in the target class (' . get_class($target) . ').');

		$time = microtime();
		$this->assertEquals('newMethodAroundAdvice' . $time, $target->newMethod($time), 'The result of newMethod() did not return the expected result while checking the introduction declaration.');
	}

	/**
	 * Checks if an introduction really introduces the new method(s) although no advice is defined for that method
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function introductionWithoutAdviceWorks() {
		$target = $this->objectManager->getObject('F3\TestPackage\IntroductionTargetClass');
		$this->assertTrue(method_exists($target, 'anotherMethod'), 'The method "anotherMethod" does not exist in the target class (' . get_class($target) . ').');
	}

	/**
	 * Checks if a target class whose constructor has one mandatory argument used for autowiring / DI stays intact if the class is adviced with the empty contructor interceptor.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mandatoryArgumentInNonAdvisedConstructorStaysIntact() {
		$target = $this->objectManager->getObject('F3\TestPackage\ClassWithOneArgument');
		$this->assertType('F3\TestPackage\InjectedClass', $target->getInjectedObject(), 'The injected class is not of the expected type or has not been injected at all.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvicedMethodsInformationByTargetClassReturnsCorrectArrayOfAdviceInformation() {
		$aopFramework = $this->objectManager->getObject('F3\FLOW3\AOP\Framework');
		$advicedMethodsInformation = $aopFramework->getAdvicedMethodsInformationByTargetClass('F3\TestPackage\BasicClass');
		$this->assertTrue(is_array($advicedMethodsInformation), 'No array was returned.');
		$this->assertTrue(count($advicedMethodsInformation) > 0, 'The returned array was empty.');
		foreach ($advicedMethodsInformation as $methodName => $groupedAdvices) {
			$this->assertTrue(is_array($groupedAdvices), 'The returned groupedAdvices values are not (all) of type array.');
			foreach ($groupedAdvices as $adviceType => $advicesInformation) {
				$this->assertTrue(is_string($adviceType) && class_exists($adviceType, TRUE), 'The advice type was invalid.');
				$this->assertTrue(is_array($advicesInformation), 'advicesInformation is not an array.');
				foreach ($advicesInformation as $adviceInformation) {
					$this->assertTrue(is_array($adviceInformation), 'adviceInformation is not an array.');
				}
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTargetAndProxyClassNamesReturnsANonEmptyArray() {
		$aopFramework = $this->objectManager->getObject('F3\FLOW3\AOP\Framework');
		$targetAndProxyClassNames = $aopFramework->getTargetAndProxyClassNames();
		$this->assertTrue(is_array($targetAndProxyClassNames), 'The returned value is not an array.');
		$this->assertTrue(count($targetAndProxyClassNames) > 0, 'The returned array was empty.');
	}
}
?>