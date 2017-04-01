<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;

/**
 * Testcase for the doctrine persistence manaager
 *
 */
class PersistenceManagerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier()
    {
        $entity = (object)array(
            'Persistence_Object_Identifier' => null
        );

        $persistenceManager = new PersistenceManager();
        $entityManagerStub = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false);
        $entityManagerStub->expects($this->any())->method('contains')->with($entity)->will($this->returnValue(true));
        $unitOfWorkStub = $this->getMock('Doctrine\ORM\UnitOfWork', array(), array(), '', false);
        $entityManagerStub->expects($this->any())->method('getUnitOfWork')->will($this->returnValue($unitOfWorkStub));
        $unitOfWorkStub->expects($this->any())->method('getEntityIdentifier')->with($entity)->will($this->returnValue(array('SomeIdentifier')));
        $this->inject($persistenceManager, 'entityManager', $entityManagerStub);

        $identifier = $persistenceManager->getIdentifierByObject($entity);

        $this->assertEquals('SomeIdentifier', $identifier);
    }
}
