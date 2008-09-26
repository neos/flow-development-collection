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

require_once('Fixture/F3_FLOW3_Tests_Persistence_Fixture_Entity2.php');
require_once('Fixture/F3_FLOW3_Tests_Persistence_Fixture_Entity3.php');
require_once('Fixture/F3_FLOW3_Tests_Persistence_Fixture_DirtyEntity.php');
require_once('Fixture/F3_FLOW3_Tests_Persistence_Fixture_CleanEntity.php');

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
	public function persistAllCanBeCalledIfNoRepositoryClassesAreFound() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue(array()));
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');
		$session = new F3::FLOW3::Persistence::Session();

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectBackend($mockBackend);
		$manager->injectSession($session);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllFindsObjectReferences() {
		$entity31 = new F3::FLOW3::Tests::Persistence::Fixture::Entity3;
		$entity32 = new F3::FLOW3::Tests::Persistence::Fixture::Entity3;
		$entity33 = new F3::FLOW3::Tests::Persistence::Fixture::Entity3;
		$entity2 = new F3::FLOW3::Tests::Persistence::Fixture::Entity2;
		$entity2->someString = 'Entity2';
		$entity2->someInteger = 42;
		$entity2->someReference = $entity31;
		$entity2->someReferenceArray = array($entity32, $entity33);

		$repository = new F3::FLOW3::Persistence::Repository;
		$repository->add($entity2);

		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('F3::FLOW3::Persistence::RepositoryInterface')->will($this->returnValue(array('F3::FLOW3::Persistence::Repository')));
		$mockReflectionService->expects($this->exactly(4))->method('getPropertyNamesByTag')->will($this->onConsecutiveCalls(array('someReference', 'someReferenceArray'), array(), array(), array()));
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('getComponent')->with('F3::FLOW3::Persistence::Repository')->will($this->returnValue($repository));
		$mockSession = $this->getMock('F3::FLOW3::Persistence::Session', array('isNew'));
		$mockSession->expects($this->exactly(4))->method('isNew')->will($this->returnValue(TRUE));
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');

			// this is the really important assertion!
		$mockBackend->expects($this->once())->method('setNewObjects')->with(
			array(
				spl_object_hash($entity2) => $entity2,
				spl_object_hash($entity31) => $entity31,
				spl_object_hash($entity32) => $entity32,
				spl_object_hash($entity33) => $entity33
			)
		);

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectComponentFactory($mockComponentFactory);
		$manager->injectSession($mockSession);
		$manager->injectBackend($mockBackend);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllUnregistersNewObjectsWithSession() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue(array()));
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');
		$mockSession = $this->getMock('F3::FLOW3::Persistence::Session', array('unregisterAllNewObjects'));
		$mockSession->expects($this->once())->method('unregisterAllNewObjects');

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectBackend($mockBackend);
		$manager->injectSession($mockSession);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllRecognizesChangedReconstitutedObjects() {
		$dirtyEntity = new F3::FLOW3::Tests::Persistence::Fixture::DirtyEntity();
		$cleanEntity = new F3::FLOW3::Tests::Persistence::Fixture::CleanEntity();
		$session = new F3::FLOW3::Persistence::Session();
		$session->registerReconstitutedObject($dirtyEntity);
		$session->registerReconstitutedObject($cleanEntity);

		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue(array()));
		$mockReflectionService->expects($this->any())->method('getPropertyNamesByTag')->will($this->returnValue(array()));
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');

			// this is the really important assertion!
		$mockBackend->expects($this->once())->method('setUpdatedObjects')->with(
			array(
				spl_object_hash($dirtyEntity) => $dirtyEntity
			)
		);

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectSession($session);
		$manager->injectBackend($mockBackend);

		$manager->persistAll();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistAllResetsDirtyStateOfObjects() {
		$mockEntity = $this->getMock('F3::FLOW3::Tests::Persistence::Fixture::DirtyEntity', array('memorizeCleanState'));
		$mockEntity->expects($this->once())->method('memorizeCleanState');

		$repository = new F3::FLOW3::Persistence::Repository;
		$repository->add($mockEntity);

		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('F3::FLOW3::Persistence::RepositoryInterface')->will($this->returnValue(array('F3::FLOW3::Persistence::Repository')));
		$mockReflectionService->expects($this->any())->method('getPropertyNamesByTag')->will($this->returnValue(array()));
		$mockClassSchemataBuilder = $this->getMock('F3::FLOW3::Persistence::ClassSchemataBuilder', array(), array(), '', FALSE);
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('getComponent')->with('F3::FLOW3::Persistence::Repository')->will($this->returnValue($repository));
		$session = new F3::FLOW3::Persistence::Session();
		$mockBackend = $this->getMock('F3::FLOW3::Persistence::BackendInterface');
		$mockBackend->expects($this->once())->method('setUpdatedObjects')->with(array(spl_object_hash($mockEntity) => $mockEntity));

		$manager = new F3::FLOW3::Persistence::Manager($mockReflectionService, $mockClassSchemataBuilder);
		$manager->injectComponentFactory($mockComponentFactory);
		$manager->injectSession($session);
		$manager->injectBackend($mockBackend);

		$manager->persistAll();
	}

}

?>