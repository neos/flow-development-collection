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
 * Functional tests for the Object Manager features
 *
 */
class ObjectManagerTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function ifOnlyOneImplementationExistsGetReturnsTheImplementationByTheSpecifiedInterface() {
		$objectByInterface = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceA');
		$objectByClassName = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation');

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation', $objectByInterface);
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation', $objectByClassName);
	}

	/**
	 * @test
	 */
	public function prototypeIsTheDefaultScopeIfNothingElseWasDefined() {
		$instanceA = new \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassB();
		$instanceB = new \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassB();

		$this->assertNotSame($instanceA, $instanceB);
	}

	/**
	 * @test
	 */
	public function interfaceObjectsHaveTheScopeDefinedInTheImplementationClassIfNothingElseWasSpecified() {
		$objectByInterface = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceA');
		$objectByClassName = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceAImplementation');

		$this->assertSame($objectByInterface, $objectByClassName);
	}
}
