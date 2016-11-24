<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Lazy Dependency Injection features
 *
 */
class LazyDependencyInjectionTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function lazyDependencyIsOnlyInjectedIfMethodOnDependencyIsCalledForTheFirstTime()
    {
        $this->objectManager->forgetInstance(Fixtures\SingletonClassA::class);

        $object = $this->objectManager->get(Fixtures\ClassWithLazyDependencies::class);
        $this->assertInstanceOf(DependencyProxy::class, $object->lazyA);

        $actualObjectB = $object->lazyA->getObjectB();
        $this->assertNotInstanceOf(DependencyProxy::class, $object->lazyA);

        $objectA = $this->objectManager->get(Fixtures\SingletonClassA::class);
        $expectedObjectB = $this->objectManager->get(Fixtures\SingletonClassB::class);
        $this->assertSame($objectA, $object->lazyA);
        $this->assertSame($expectedObjectB, $actualObjectB);
    }

    /**
     * @test
     */
    public function dependencyIsInjectedDirectlyIfLazyIsTurnedOff()
    {
        $object = $this->objectManager->get(Fixtures\ClassWithLazyDependencies::class);
        $this->assertInstanceOf(Fixtures\SingletonClassC::class, $object->eagerC);
    }

    /**
     * @test
     */
    public function lazyDependencyIsInjectedIntoAllClassesWhichNeedItIfItIsUsedTheFirstTime()
    {
        $this->objectManager->forgetInstance(Fixtures\SingletonClassA::class);
        $this->objectManager->forgetInstance(Fixtures\SingletonClassB::class);

        $object1 = $this->objectManager->get(Fixtures\ClassWithLazyDependencies::class);
        $object2 = $this->objectManager->get(Fixtures\AnotherClassWithLazyDependencies::class);

        $this->assertInstanceOf(DependencyProxy::class, $object1->lazyA);
        $this->assertInstanceOf(DependencyProxy::class, $object2->lazyA);

        $object2->lazyA->getObjectB();

        $objectA = $this->objectManager->get(Fixtures\SingletonClassA::class);
        $this->assertSame($objectA, $object1->lazyA);
        $this->assertSame($objectA, $object2->lazyA);
    }
}
