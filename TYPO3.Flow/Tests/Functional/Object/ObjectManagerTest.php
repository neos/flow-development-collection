<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functional tests for the Object Manager features
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectManagerTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifOnlyOneImplementationExistsGetReturnsTheImplementationByTheSpecifiedInterface() {
		$objectByInterface = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceA');
		$objectByClassName = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceAImplementation');

		$this->assertInstanceOf('F3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceAImplementation', $objectByInterface);
		$this->assertInstanceOf('F3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceAImplementation', $objectByClassName);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function prototypeIsTheDefaultScopeIfNothingElseWasDefined() {
		$instanceA = new \F3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassB();
		$instanceB = new \F3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassB();

		$this->assertNotSame($instanceA, $instanceB);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function interfaceObjectsHaveTheScopeDefinedInTheImplementationClassIfNothingElseWasSpecified() {
		$objectByInterface = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceA');
		$objectByClassName = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceAImplementation');

		$this->assertSame($objectByInterface, $objectByClassName);
	}
}
?>