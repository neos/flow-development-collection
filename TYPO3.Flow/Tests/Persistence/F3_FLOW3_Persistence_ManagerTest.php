<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence;

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
 * @version $Id$
 */

/**
 * Testcase for the Persistence Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSessionReturnsTheCurrentPersistenceSession() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);

		$session = new F3::FLOW3::Persistence::Session();
		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectSession($session);

		$this->assertType('F3::FLOW3::Persistence::Session', $manager->getSession());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeRecognizesEntityAndValueObjects() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockReflectionService->expects($this->any())->method('getClassNamesByTag')->will($this->onConsecutiveCalls(array('EntityClass'), array('ValueClass')));
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
			// with() here holds the important assertion
		$mockClassSchemataBuilder->expects($this->once())->method('build')->with(array('EntityClass', 'ValueClass'))->will($this->returnValue(array()));
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectBackend($mockBackend);
		$manager->initialize();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function persistAllWorksIfNoRepositoryClassesAreFound() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');

		$mockReflectionService->expects($this->any())->method('getClassNamesByTag')->will($this->returnValue(array()));

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectBackend($mockBackend);

		$manager->persistAll();
	}
}

?>