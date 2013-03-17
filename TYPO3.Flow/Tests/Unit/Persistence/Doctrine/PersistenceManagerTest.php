<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;

/**
 * Testcase for the doctrine persistence manaager
 *
 */
class PersistenceManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier() {
		$entity = (object)array(
			'Persistence_Object_Identifier' => NULL
		);

		$persistenceManager = new PersistenceManager();
		$entityManagerStub = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', FALSE);
		$entityManagerStub->expects($this->any())->method('contains')->with($entity)->will($this->returnValue(TRUE));
		$unitOfWorkStub = $this->getMock('Doctrine\ORM\UnitOfWork', array(), array(), '', FALSE);
		$entityManagerStub->expects($this->any())->method('getUnitOfWork')->will($this->returnValue($unitOfWorkStub));
		$unitOfWorkStub->expects($this->any())->method('getEntityIdentifier')->with($entity)->will($this->returnValue(array('SomeIdentifier')));
		$this->inject($persistenceManager, 'entityManager', $entityManagerStub);

		$identifier = $persistenceManager->getIdentifierByObject($entity);

		$this->assertEquals('SomeIdentifier', $identifier);
	}

}
?>