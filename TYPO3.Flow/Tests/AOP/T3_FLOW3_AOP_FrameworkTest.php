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
 * Testcase for the AOP Framework class
 * 
 * @package		Framework
 * @version 	$Id:T3_FLOW3_AOP_FrameworkTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_FrameworkTest extends T3_Testing_BaseTestCase {
	
	/**
	 * Checks if constructor parameters still work after a component class has been proxied.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorsOfProxiedClassesAreStillIntact() {
		$componentWithConstructor = $this->componentManager->getComponent('T3_TestPackage_ClassWithOptionalConstructorArguments', 'modified argument1', 'modified argument2', 'modified argument3');
		$this->assertEquals('modified argument1', $componentWithConstructor->argument1, 'The property set through the first constructor argument does not contain the expected value.');
		$this->assertEquals('modified argument2', $componentWithConstructor->argument2, 'The property set through the second constructor argument does not contain the expected value.');
		$this->assertEquals('modified argument3', $componentWithConstructor->argument3, 'The property set through the third constructor argument does not contain the expected value.');
	}
	
	/**
	 * Checks if the "chinese advice" is active when calling the getSomeProperty method of the BasicClass
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aroundAdviceBasicallyworks() {
		$basicObject = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
		$this->assertType('T3_TestPackage_BasicClass_AOPProxy', $basicObject, 'The basic object seems not to be a proxy object!');
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
		$aspect = $this->componentManager->getComponent('T3_TestPackage_GetSomeChinesePropertyAspect');
		$time = 'before' . microtime();
		$target = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
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
		$aspect = $this->componentManager->getComponent('T3_TestPackage_GetSomeChinesePropertyAspect');
		$time = 'afterReturning' . microtime();
		$target = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
		$target->setSomeProperty($time);
		$target->getSomeProperty();
		$this->assertEquals($time, $aspect->getFlags('afterReturning'), 'The internal flag of the aspect did not contain the expected value after testing the After Returning advice.');
	}
	
	/**
	 * Checks if an after throwing advice basically works.
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function afterThrowingAdviceBasicallyWorks() {
		$aspect = $this->componentManager->getComponent('T3_TestPackage_GetSomeChinesePropertyAspect');
		$time = 'afterThrowing' . microtime();
		$target = $this->componentManager->getComponent('T3_TestPackage_BasicClass');
		try {
			$target->throwAnException('RuntimeException', $time);		
		} catch (Exception $exception) {
			
		}
		$this->assertEquals($time, $aspect->getFlags('afterThrowing'), 'The internal flag of the aspect did not contain the expected value after testing the After Throwing advice.');
	}
	
	/**
	 * Checks if an introduction declaration basically works.
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function introductionDeclarationBasicallyWorks() {
		$aspect = $this->componentManager->getComponent('T3_TestPackage_IntroductionAspect');
		$target = $this->componentManager->getComponent('T3_TestPackage_IntroductionTargetClass');
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
		$target = $this->componentManager->getComponent('T3_TestPackage_IntroductionTargetClass');
		$this->assertTrue(method_exists($target, 'anotherMethod'), 'The method "anotherMethod" does not exist in the target class (' . get_class($target) . ').');
	}
	
	/**
	 * Checks if a target class whose constructor has one mandatory argument used for autowiring / DI stays intact if the class is adviced with the empty contructor interceptor.
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mandatoryArgumentInNonAdvisedConstructorStaysIntact() {
		$target = $this->componentManager->getComponent('T3_TestPackage_ClassWithOneConstructorArgument');
		$this->assertType('T3_TestPackage_InjectedClass', $target->getInjectedComponent(), 'The injected class is not of the expected type or has not been injected at all.');
	}
}
?>