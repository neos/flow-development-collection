<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Testcase for the Object Factory
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
		$overridingConstructorArguments = array(
			1 => new \F3\FLOW3\Object\ConfigurationArgument(1, 'test1', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			2 => new \F3\FLOW3\Object\ConfigurationArgument(2, 'test2', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
			3 => new \F3\FLOW3\Object\ConfigurationArgument(3, 'test3', \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE),
		);
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with($objectName)->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->once())->method('getObjectConfiguration')->with($objectName)->will($this->returnValue($objectConfiguration));
		$this->mockObjectBuilder->expects($this->once())->method('createObject')->with($objectName, $objectConfiguration, $overridingConstructorArguments);

		$this->objectFactory->create($objectName, 'test1', 'test2', 'test3');
	}

}
?>