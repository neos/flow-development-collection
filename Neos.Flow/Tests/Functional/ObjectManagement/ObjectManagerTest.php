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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Object Manager features
 */
class ObjectManagerTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function ifOnlyOneImplementationExistsGetReturnsTheImplementationByTheSpecifiedInterface()
    {
        $objectByInterface = $this->objectManager->get(Fixtures\InterfaceA::class);
        $objectByClassName = $this->objectManager->get(Fixtures\InterfaceAImplementation::class);

        self::assertInstanceOf(Fixtures\InterfaceAImplementation::class, $objectByInterface);
        self::assertInstanceOf(Fixtures\InterfaceAImplementation::class, $objectByClassName);
    }

    /**
     * @test
     */
    public function prototypeIsTheDefaultScopeIfNothingElseWasDefined()
    {
        $instanceA = new Fixtures\PrototypeClassB();
        $instanceB = new Fixtures\PrototypeClassB();

        self::assertNotSame($instanceA, $instanceB);
    }

    /**
     * @test
     */
    public function interfaceObjectsHaveTheScopeDefinedInTheImplementationClassIfNothingElseWasSpecified()
    {
        $objectByInterface = $this->objectManager->get(Fixtures\InterfaceA::class);
        $objectByClassName = $this->objectManager->get(Fixtures\InterfaceAImplementation::class);

        self::assertSame($objectByInterface, $objectByClassName);
    }

    /**
     * @test
     */
    public function shutdownObjectMethodIsCalledAfterRegistrationViaConstructor()
    {
        $entity = new Fixtures\PrototypeClassG();
        $entity->setName('Shutdown');

        /**
         * When shutting down the ObjectManager shutdownObject() on Fixtures\TestEntityWithShutdown is called
         * and sets $destructed property to true
         */
        \Neos\Flow\Core\Bootstrap::$staticObjectManager->shutdown();

        self::assertTrue($entity->isDestructed());
    }

    /**
     * ObjectManager has to be shutdown before the ConfigurationManager
     * @see https://github.com/neos/flow-development-collection/issues/2183
     * @test
     */
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
    
    /**
     * @test
     */
    public function virtualObjectsCanBeInstantiated()
    {
        /** @var Fixtures\Flow175\OuterPrototype $object1 */
        $object1 = $this->objectManager->get('Neos.Flow:VirtualObject1');
        /** @var Fixtures\Flow175\OuterPrototype $object2 */
        $object2 = $this->objectManager->get('Neos.Flow:VirtualObject2');

        self::assertSame('Hello Bastian!', $object1->getInner()->greet('Bastian'));
        self::assertSame('Hello Bastian from a different greeter!', $object2->getInner()->greet('Bastian'));
    }
}
