<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */

/**
 * Testcase for the Object Factory
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class FactoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Object\Builder
	 */
	protected $mockObjectBuilder;

	/**
	 * @var \F3\FLOW3\Object\Factory
	 */
	protected $mockObjectFactory;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$this->mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder');
		$this->objectFactory = new \F3\FLOW3\Object\Factory();
		$this->objectFactory->injectObjectManager($this->mockObjectManager);
		$this->objectFactory->injectObjectBuilder($this->mockObjectBuilder);
	}

	/**
	 * Checks if create() calls the object builder as expected
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createCallsObjectBuilderAsExpected() {
		$objectName = 'F3\Virtual\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$objectConfiguration->setScope('prototype');
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with($objectName)->will($this->returnValue($objectConfiguration));
		$this->mockObjectBuilder->expects($this->once())->method('createObject')->with($objectName, $objectConfiguration, array());

		$this->objectFactory->create($objectName);
	}

	/**
	 * Checks if create() fails on non-existing objects
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObject
	 */
	public function createFailsOnNonExistentObject() {
		$this->objectFactory->create('F3\TestPackage\ThisClassDoesNotExist');
	}

	/**
	 * Checks if create() only delivers prototypes
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\FLOW3\Object\Exception\WrongScope
	 */
	public function createThrowsExceptionWhenAskedForNonPrototype() {
		$objectName = 'F3\Virtual\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with($objectName)->will($this->returnValue($objectConfiguration));

		$this->objectFactory->create($objectName);
	}

	/**
	 * Checks if create() passes arguments to the object builder
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createPassesArgumentsToObjectBuilder() {
		$objectName = 'F3\Virtual\BasicClass';
		$objectConfiguration = new \F3\FLOW3\Object\Configuration($objectName);
		$objectConfiguration->setScope('prototype');
		$overridingArguments = array(
			1 => new \F3\FLOW3\Object\ConfigurationArgument(1, 'test1', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			2 => new \F3\FLOW3\Object\ConfigurationArgument(2, 'test2', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			3 => new \F3\FLOW3\Object\ConfigurationArgument(3, 'test3', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		);
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with($objectName)->will($this->returnValue($objectConfiguration));
		$this->mockObjectBuilder->expects($this->once())->method('createObject')->with($objectName, $objectConfiguration, $overridingArguments);

		$this->objectFactory->create($objectName, 'test1', 'test2', 'test3');
	}

}
?>