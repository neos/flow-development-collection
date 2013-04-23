<?php
namespace TYPO3\Flow\Tests\Functional\Object;

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
 * Functional tests for the Lazy Dependency Injection features
 *
 */
class LazyDependencyInjectionTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function lazyDependencyIsOnlyInjectedIfMethodOnDependencyIsCalledForTheFirstTime() {
		$this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA');

		$object = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies');
		$this->assertInstanceOf('TYPO3\Flow\Object\DependencyInjection\DependencyProxy', $object->lazyA);

		$actualObjectB = $object->lazyA->getObjectB();
		$this->assertNotInstanceOf('TYPO3\Flow\Object\DependencyInjection\DependencyProxy', $object->lazyA);

		$objectA = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA');
		$expectedObjectB = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB');
		$this->assertSame($objectA, $object->lazyA);
		$this->assertSame($expectedObjectB, $actualObjectB);
	}

	/**
	 * @test
	 */
	public function dependencyIsInjectedDirectlyIfLazyIsTurnedOff() {
		$object = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies');
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC', $object->eagerC);
	}

	/**
	 * @test
	 */
	public function lazyDependencyIsInjectedIntoAllClassesWhichNeedItIfItIsUsedTheFirstTime() {
		$this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA');
		$this->objectManager->forgetInstance('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB');

		$object1 = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies');
		$object2 = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\AnotherClassWithLazyDependencies');

		$this->assertInstanceOf('TYPO3\Flow\Object\DependencyInjection\DependencyProxy', $object1->lazyA);
		$this->assertInstanceOf('TYPO3\Flow\Object\DependencyInjection\DependencyProxy', $object2->lazyA);

		$object2->lazyA->getObjectB();

		$objectA = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA');
		$this->assertSame($objectA, $object1->lazyA);
		$this->assertSame($objectA, $object2->lazyA);
	}

}
?>