<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Functional tests for the Lazy Dependency Injection features
 *
 */
class LazyDependencyInjectionTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function lazyDependencyIsOnlyInjectedIfMethodOnDependencyIsCalledForTheFirstTime()
    {
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
    public function dependencyIsInjectedDirectlyIfLazyIsTurnedOff()
    {
        $object = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies');
        $this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC', $object->eagerC);
    }

    /**
     * @test
     */
    public function lazyDependencyIsInjectedIntoAllClassesWhichNeedItIfItIsUsedTheFirstTime()
    {
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
