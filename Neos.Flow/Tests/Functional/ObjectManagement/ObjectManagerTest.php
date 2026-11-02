<?php

declare(strict_types=1);

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

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\Flow175\OuterPrototype;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\InterfaceA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\InterfaceAImplementation;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassB;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassG;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional tests for the Object Manager features
 */
final class ObjectManagerTest extends FunctionalTestCase
{
    #[Test]
    public function ifOnlyOneImplementationExistsGetReturnsTheImplementationByTheSpecifiedInterface()
    {
        $objectByInterface = $this->objectManager->get(InterfaceA::class);
        $objectByClassName = $this->objectManager->get(InterfaceAImplementation::class);

        self::assertInstanceOf(InterfaceAImplementation::class, $objectByInterface);
        self::assertInstanceOf(InterfaceAImplementation::class, $objectByClassName);
        self::assertSame($objectByClassName, $objectByInterface, sprintf('Instance %d does not equal %d', spl_object_id($objectByClassName), spl_object_id($objectByInterface)));
    }

    #[Test]
    public function requestingTheImplementationAndThenTheInterfaceWorks()
    {
        $this->objectManager->forgetInstance(Fixtures\InterfaceAImplementation::class);
        $this->objectManager->forgetInstance(Fixtures\InterfaceA::class);
        $objectByClassName = $this->objectManager->get(Fixtures\InterfaceAImplementation::class);
        $objectByInterface = $this->objectManager->get(Fixtures\InterfaceA::class);

        self::assertInstanceOf(Fixtures\InterfaceA::class, $objectByInterface);
        self::assertInstanceOf(Fixtures\InterfaceA::class, $objectByClassName);
        self::assertSame($objectByClassName, $objectByInterface, sprintf('Instance %d does not equal %d', spl_object_id($objectByClassName), spl_object_id($objectByInterface)));
    }

    #[Test]
    public function prototypeIsTheDefaultScopeIfNothingElseWasDefined()
    {
        $instanceA = new PrototypeClassB();
        $instanceB = new PrototypeClassB();

        self::assertNotSame($instanceA, $instanceB);
    }

    #[Test]
    public function interfaceObjectsHaveTheScopeDefinedInTheImplementationClassIfNothingElseWasSpecified()
    {
        $objectByInterface = $this->objectManager->get(InterfaceA::class);
        $objectByClassName = $this->objectManager->get(InterfaceAImplementation::class);

        self::assertSame($objectByInterface, $objectByClassName);
    }

    #[Test]
    public function shutdownObjectMethodIsCalledAfterRegistrationViaConstructor()
    {
        $entity = new PrototypeClassG();
        $entity->setName('Shutdown');

        /**
         * When shutting down the ObjectManager shutdownObject() on Fixtures\TestEntityWithShutdown is called
         * and sets $destructed property to true
         */
        Bootstrap::$staticObjectManager->shutdown();

        self::assertTrue($entity->isDestructed());
    }

    /**
     * ObjectManager has to be shutdown before the ConfigurationManager
     * @see https://github.com/neos/flow-development-collection/issues/2183
     */
    #[Test]
    public function objectManagerShutdownSlotIsRegisteredBeforeConfigurationManager(): void
    {
        $dispatcher = $this->objectManager->get(Dispatcher::class);
        $slots = $dispatcher->getSlots(Bootstrap::class, 'bootstrapShuttingDown');

        $slotClassNames = array_column($slots, 'class');
        $relevantSlots = array_filter($slotClassNames, function (string $className) {
            return in_array(
                $className,
                [
                    ObjectManagerInterface::class,
                    ConfigurationManager::class
                ],
                true
            );
        });

        $first = reset($relevantSlots);
        $last = end($relevantSlots);

        self::assertSame(ObjectManagerInterface::class, $first);
        self::assertSame(ConfigurationManager::class, $last);
    }

    #[Test]
    public function virtualObjectsCanBeInstantiated()
    {
        /** @var OuterPrototype $object1 */
        $object1 = $this->objectManager->get('Neos.Flow:VirtualObject1');
        /** @var OuterPrototype $object2 */
        $object2 = $this->objectManager->get('Neos.Flow:VirtualObject2');

        self::assertSame('Hello Bastian!', $object1->getInner()->greet('Bastian'));
        self::assertSame('Hello Bastian from a different greeter!', $object2->getInner()->greet('Bastian'));
    }

    #[Test]
    public function interfaceObjectUsesClassConfigurationForInjection()
    {
        /** @var \Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassImplementingInterfaceToTestConstructorInjection $object1 */
        $object1 = $this->objectManager->get(\Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\InterfaceToTestConstructorInjection::class);
        self::assertInstanceOf(PersistenceManagerInterface::class, $object1->persistenceManager);
        self::assertInstanceOf(VariableFrontend::class, $object1->cache);
    }
}
