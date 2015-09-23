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

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the doctrine persistence manager
 *
 */
class PersistenceManagerTest extends UnitTestCase
{
    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockEntityManager;

    /**
     * @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockUnitOfWork;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConnection;

    /**
     * @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSystemLogger;

    public function setUp()
    {
        $this->persistenceManager = $this->getMockBuilder(\TYPO3\Flow\Persistence\Doctrine\PersistenceManager::class)->setMethods(array('emitAllObjectsPersisted'))->getMock();

        $this->mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('isOpen')->will($this->returnValue(true));
        $this->inject($this->persistenceManager, 'entityManager', $this->mockEntityManager);

        $this->mockUnitOfWork = $this->getMockBuilder(\Doctrine\ORM\UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('getUnitOfWork')->will($this->returnValue($this->mockUnitOfWork));

        $this->mockConnection = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('getConnection')->will($this->returnValue($this->mockConnection));

        $this->mockSystemLogger = $this->getMockBuilder(\TYPO3\Flow\Log\SystemLoggerInterface::class)->getMock();
        $this->inject($this->persistenceManager, 'systemLogger', $this->mockSystemLogger);
    }

    /**
     * @test
     */
    public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier()
    {
        $entity = (object)array(
            'Persistence_Object_Identifier' => null
        );

        $this->mockEntityManager->expects($this->any())->method('contains')->with($entity)->will($this->returnValue(true));
        $this->mockUnitOfWork->expects($this->any())->method('getEntityIdentifier')->with($entity)->will($this->returnValue(array('SomeIdentifier')));

        $this->assertEquals('SomeIdentifier', $this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Persistence\Exception
     * @expectedExceptionMessageRegExp /^Detected modified or new objects/
     */
    public function persistAllThrowsExceptionIfTryingToPersistNonWhitelistedObjectsAndOnlyWhitelistedObjectsFlagIsTrue()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = array(spl_object_hash($mockObject) => $mockObject);
        $scheduledEntityDeletes = array();
        $scheduledEntityInsertions = array();
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue($scheduledEntityUpdates));
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityDeletions')->will($this->returnValue($scheduledEntityDeletes));
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue($scheduledEntityInsertions));

        $this->mockEntityManager->expects($this->never())->method('flush');

        $this->persistenceManager->persistAll(true);
    }

    /**
     * @test
     */
    public function persistAllRespectsObjectWhitelistIfOnlyWhitelistedObjectsFlagIsTrue()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = array(spl_object_hash($mockObject) => $mockObject);
        $scheduledEntityDeletes = array();
        $scheduledEntityInsertions = array();
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue($scheduledEntityUpdates));
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityDeletions')->will($this->returnValue($scheduledEntityDeletes));
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue($scheduledEntityInsertions));

        $this->mockEntityManager->expects($this->once())->method('flush');

        $this->persistenceManager->whitelistObject($mockObject);
        $this->persistenceManager->persistAll(true);
    }

    /**
     * @test
     */
    public function persistAllAbortsIfConnectionIsClosed()
    {
        $mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $mockEntityManager->expects($this->atLeastOnce())->method('isOpen')->will($this->returnValue(false));
        $this->inject($this->persistenceManager, 'entityManager', $mockEntityManager);

        $mockEntityManager->expects($this->never())->method('flush');
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllEmitsAllObjectsPersistedSignal()
    {
        $this->mockEntityManager->expects($this->once())->method('flush');
        $this->persistenceManager->expects($this->once())->method('emitAllObjectsPersisted');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Error\Exception
     */
    public function persistAllReconnectsConnectionOnFailure()
    {
        $this->mockEntityManager->expects($this->exactly(2))->method('flush')->will($this->throwException(new \TYPO3\Flow\Error\Exception('Dummy connection error')));
        $this->mockConnection->expects($this->at(0))->method('close');
        $this->mockConnection->expects($this->at(1))->method('connect');

        $this->persistenceManager->persistAll();
    }
}
