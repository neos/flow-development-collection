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
