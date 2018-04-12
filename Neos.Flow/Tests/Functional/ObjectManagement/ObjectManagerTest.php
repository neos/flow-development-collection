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

        $this->assertInstanceOf(Fixtures\InterfaceAImplementation::class, $objectByInterface);
        $this->assertInstanceOf(Fixtures\InterfaceAImplementation::class, $objectByClassName);
    }

    /**
     * @test
     */
    public function prototypeIsTheDefaultScopeIfNothingElseWasDefined()
    {
        $instanceA = new Fixtures\PrototypeClassB();
        $instanceB = new Fixtures\PrototypeClassB();

        $this->assertNotSame($instanceA, $instanceB);
    }

    /**
     * @test
     */
    public function interfaceObjectsHaveTheScopeDefinedInTheImplementationClassIfNothingElseWasSpecified()
    {
        $objectByInterface = $this->objectManager->get(Fixtures\InterfaceA::class);
        $objectByClassName = $this->objectManager->get(Fixtures\InterfaceAImplementation::class);

        $this->assertSame($objectByInterface, $objectByClassName);
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
         * and sets $destructed property to TRUE
         */
        \Neos\Flow\Core\Bootstrap::$staticObjectManager->shutdown();

        $this->assertTrue($entity->isDestructed());
    }
}
