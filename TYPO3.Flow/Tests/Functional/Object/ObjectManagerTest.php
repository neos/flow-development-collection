<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Functional tests for the Object Manager features
 *
 */
class ObjectManagerTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function ifOnlyOneImplementationExistsGetReturnsTheImplementationByTheSpecifiedInterface()
    {
        $objectByInterface = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceA::class);
        $objectByClassName = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation::class);

        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation::class, $objectByInterface);
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation::class, $objectByClassName);
    }

    /**
     * @test
     */
    public function prototypeIsTheDefaultScopeIfNothingElseWasDefined()
    {
        $instanceA = new \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassB();
        $instanceB = new \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassB();

        $this->assertNotSame($instanceA, $instanceB);
    }

    /**
     * @test
     */
    public function interfaceObjectsHaveTheScopeDefinedInTheImplementationClassIfNothingElseWasSpecified()
    {
        $objectByInterface = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceA::class);
        $objectByClassName = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation::class);

        $this->assertSame($objectByInterface, $objectByClassName);
    }
}
