<?php
namespace TYPO3\FLOW3\Tests\Functional\Aop;

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
 * Testcase for the AOP Framework class
 *
 */
class FrameworkTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function resultOfSayHelloMethodIsModifiedByWorldAdvice() {
		$targetClass = new Fixtures\TargetClass01();
		$this->assertSame('Hello World', $targetClass->sayHello());
	}

	/**
	 * @test
	 */
	public function adviceRecoversFromException() {
		$targetClass = new Fixtures\TargetClass01();
		try {
			$targetClass->sayHelloAndThrow(TRUE);
		} catch(\Exception $e) {}
		$this->assertSame('Hello World', $targetClass->sayHelloAndThrow(FALSE));
	}

	/**
	 * @test
	 */
	public function resultOfGreetMethodIsModifiedBySpecialNameAdvice() {
		$targetClass = new Fixtures\TargetClass01();
		$this->assertSame('Hello, me', $targetClass->greet('FLOW3'));
		$this->assertSame('Hello, Christopher', $targetClass->greet('Christopher'));
	}

	/**
	 * @test
	 */
	public function containWithSplObjectStorageInRuntimeEvaluation() {
		$targetClass = new Fixtures\TargetClass01();
		$name = new \TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Name('FLOW3');
		$otherName = new \TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Name('TYPO3');
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach($name);
		$targetClass->setCurrentName($name);
		$this->assertEquals('Hello, special guest', $targetClass->greetMany($splObjectStorage));
		$targetClass->setCurrentName(NULL);
		$this->assertEquals('Hello, FLOW3', $targetClass->greetMany($splObjectStorage));
		$targetClass->setCurrentName($otherName);
		$this->assertEquals('Hello, FLOW3', $targetClass->greetMany($splObjectStorage));
	}

	/**
	 * @test
	 */
	public function constructorAdvicesAreInvoked() {
		$targetClass = new Fixtures\TargetClass01();
		$this->assertSame('AVRO RJ100 is lousier than A-380', $targetClass->constructorResult);
	}

	/**
	 * @test
	 */
	public function withinPointcutsAlsoAcceptClassNames() {
		$targetClass = new Fixtures\TargetClass01();
		$this->assertSame('FLOW3 is Rocket Science', $targetClass->sayWhatFlow3Is(), 'TargetClass01');
		$childClass = new Fixtures\ChildClassOfTargetClass01();
		$this->assertSame('FLOW3 is not Rocket Science', $childClass->sayWhatFlow3Is(), 'Child class of TargetClass01');
	}

	/**
	 * @test
	 */
	public function adviceInformationIsAlsoBuiltWhenTheTargetClassIsUnserialized() {
		$className = 'TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\TargetClass01';
		$targetClass = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		$this->assertSame('Hello, me', $targetClass->greet('FLOW3'));
	}

	/**
	 * @test
	 */
	public function afterReturningAdviceIsTakingEffect() {
		$targetClass = new Fixtures\TargetClass02();
		$targetClass->publicTargetMethod('foo');
		$this->assertTrue($targetClass->afterReturningAdviceWasInvoked);
	}

	/**
	 * Due to the way the proxy classes are rendered, lifecycle methods such as
	 * initializeObject() were called twice if the constructor is adviced by some
	 * aspect. This test makes sure that any code after the AOP advice code is only
	 * executed once.
	 *
	 * Test for bugfix #25610
	 *
	 * @test
	 */
	public function codeAfterTheAopCodeInTheProxyMethodIsOnlyCalledOnce() {
		$targetClass = new Fixtures\TargetClass01();
		$this->assertEquals(1, $targetClass->initializeObjectCallCounter);
	}

	/**
	 * Checks if the target class is protected, the advice is woven in anyway.
	 * The necessary advice is defined in BaseFunctionalityAspect.
	 *
	 * Test for bugfix #2581
	 *
	 * @test
	 */
	public function protectedMethodsCanAlsoBeAdviced() {
		$targetClass = new Fixtures\TargetClass02();
		$result = $targetClass->publicTargetMethod('foo');
		$this->assertEquals('foo bar', $result);
	}

	/**
	 * @test
	 */
	public function resultOfGreetObjectMethodIsModifiedByAdvice() {
		$targetClass = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\TargetClass01');
		$name = new \TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Name('TYPO3');
		$this->assertSame('Hello, old friend', $targetClass->greetObject($name), 'Aspect should greet with "old friend" if the name property equals "TYPO3"');
		$name = new \TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Name('Christopher');
		$this->assertSame('Hello, Christopher', $targetClass->greetObject($name));
	}

	/**
	 * @test
	 */
	public function thisIsSupportedInMethodRuntimeCondition() {
		$targetClass = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\TargetClass01');
		$name = new \TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Name('Phoenix');
		$targetClass->setCurrentName($name);
		$this->assertSame('Hello, you', $targetClass->greetObject($name), 'Aspect should greet with "you" if the current name equals the name argument');

		$name = new \TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Name('Christopher');
		$targetClass->setCurrentName(NULL);
		$this->assertSame('Hello, Christopher', $targetClass->greetObject($name), 'Aspect should greet with given name if the current name is not equal to the name argument');
	}

	/**
	 * @test
	 */
	public function globalObjectsAreSupportedInMethodRuntimeCondition() {
		$targetClass = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\TargetClass01');
		$this->assertSame('Hello, superstar', $targetClass->greet('Robbie'), 'Aspect should greet with "superstar" if the global context getNameOfTheWeek equals the given name');
		$this->assertSame('Hello, Christopher', $targetClass->greet('Christopher'), 'Aspect should greet with given name if the global context getNameOfTheWeek does not equal the given name');
	}

	/**
	 * An interface with a method which is not adviced and thus not implemented can be introduced.
	 * The proxy class contains a place holder implementation of that introduced method.
	 *
	 * @test
	 */
	public function interfaceWithMethodCanBeIntroduced() {
		$targetClass = new Fixtures\TargetClass03();

		$this->assertInstanceOf('TYPO3\FLOW3\Tests\Functional\Aop\Fixtures\Introduced01Interface', $targetClass);
		$this->assertTrue(method_exists($targetClass, 'introducedMethod01'));
		$this->assertTrue(method_exists($targetClass, 'introducedMethodWithArguments'));
	}

	/**
	 * Public and protected properties can be introduced.
	 *
	 * @test
	 */
	public function propertiesCanBeIntroduced() {
		$targetClass = new Fixtures\TargetClass03();

		$this->assertTrue(property_exists(get_class($targetClass), 'introducedPublicProperty'));
		$this->assertTrue(property_exists(get_class($targetClass), 'introducedProtectedProperty'));
	}

	/**
	 * @test
	 */
	public function methodArgumentsCanBeSetInTheJoinpoint() {
		$targetClass = new Fixtures\TargetClass01();
		$result = $targetClass->greet('Andi');
		$this->assertEquals('Hello, Robert', $result, 'The method argument "name" has not been changed as expected by the "changeNameArgumentAdvice".');
	}
}
?>