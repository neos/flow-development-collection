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
        $this->objectManager->forgetInstance(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA::class);

        $object = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies::class);
        $this->assertInstanceOf(\TYPO3\Flow\Object\DependencyInjection\DependencyProxy::class, $object->lazyA);

        $actualObjectB = $object->lazyA->getObjectB();
        $this->assertNotInstanceOf(\TYPO3\Flow\Object\DependencyInjection\DependencyProxy::class, $object->lazyA);

        $objectA = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA::class);
        $expectedObjectB = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB::class);
        $this->assertSame($objectA, $object->lazyA);
        $this->assertSame($expectedObjectB, $actualObjectB);
    }

    /**
     * @test
     */
    public function dependencyIsInjectedDirectlyIfLazyIsTurnedOff()
    {
        $object = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies::class);
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC::class, $object->eagerC);
    }

    /**
     * @test
     */
    public function lazyDependencyIsInjectedIntoAllClassesWhichNeedItIfItIsUsedTheFirstTime()
    {
        $this->objectManager->forgetInstance(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA::class);
        $this->objectManager->forgetInstance(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB::class);

        $object1 = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassWithLazyDependencies::class);
        $object2 = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\AnotherClassWithLazyDependencies::class);

        $this->assertInstanceOf(\TYPO3\Flow\Object\DependencyInjection\DependencyProxy::class, $object1->lazyA);
        $this->assertInstanceOf(\TYPO3\Flow\Object\DependencyInjection\DependencyProxy::class, $object2->lazyA);

        $object2->lazyA->getObjectB();

        $objectA = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA::class);
        $this->assertSame($objectA, $object1->lazyA);
        $this->assertSame($objectA, $object2->lazyA);
    }
}
