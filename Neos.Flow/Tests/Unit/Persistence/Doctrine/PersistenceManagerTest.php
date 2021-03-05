<?php
namespace Neos\Flow\Tests\Unit\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the doctrine persistence manager
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
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSystemLogger;

    /**
     * @var \PHPUnit_Framework_MockObject_InvocationMocker
     */
    protected $mockPing;

    public function setUp()
    {
        $this->persistenceManager = $this->getMockBuilder(\Neos\Flow\Persistence\Doctrine\PersistenceManager::class)->setMethods(['emitAllObjectsPersisted'])->getMock();

        $this->mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('isOpen')->willReturn(true);
        $this->inject($this->persistenceManager, 'entityManager', $this->mockEntityManager);

        $this->mockUnitOfWork = $this->getMockBuilder(\Doctrine\ORM\UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('getUnitOfWork')->willReturn($this->mockUnitOfWork);

        $this->mockConnection = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)->disableOriginalConstructor()->getMock();
        $this->mockPing = $this->mockConnection->expects($this->atMost(1))->method('ping');
        $this->mockPing->willReturn(true);
        $this->mockEntityManager->expects($this->any())->method('getConnection')->willReturn($this->mockConnection);

        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->persistenceManager->injectLogger($this->mockSystemLogger);

        $this->inject($this->persistenceManager, 'throwableStorage', $this->getMockBuilder(ThrowableStorageInterface::class)->getMock());
    }

    /**
     * @test
     */
    public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier()
    {
        $entity = (object)[
            'Persistence_Object_Identifier' => null
        ];

        $this->mockEntityManager->expects($this->any())->method('contains')->with($entity)->willReturn(true);
        $this->mockUnitOfWork->expects($this->any())->method('getEntityIdentifier')->with($entity)->willReturn(['SomeIdentifier']);

        $this->assertEquals('SomeIdentifier', $this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Persistence\Exception
     * @expectedExceptionMessageRegExp /^Detected modified or new objects/
     */
    public function persistAllThrowsExceptionIfTryingToPersistNonAllowedObjectsAndOnlyAllowedObjectsFlagIsTrue()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityUpdates')->willReturn($scheduledEntityUpdates);
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityDeletions')->willReturn($scheduledEntityDeletes);
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityInsertions')->willReturn($scheduledEntityInsertions);

        $this->mockEntityManager->expects($this->never())->method('flush');

        $this->persistenceManager->persistAll(true);
    }

    /**
     * @test
     */
    public function persistAllRespectsObjectAllowedIfOnlyAllowedObjectsFlagIsTrue()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityUpdates')->willReturn($scheduledEntityUpdates);
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityDeletions')->willReturn($scheduledEntityDeletes);
        $this->mockUnitOfWork->expects($this->any())->method('getScheduledEntityInsertions')->willReturn($scheduledEntityInsertions);

        $this->mockEntityManager->expects($this->once())->method('flush');

        $this->persistenceManager->allowObject($mockObject);
        $this->persistenceManager->persistAll(true);
    }

    /**
     * @test
     */
    public function persistAllAbortsIfConnectionIsClosed()
    {
        $mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $mockEntityManager->expects($this->atLeastOnce())->method('isOpen')->willReturn(false);
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
     */
    public function persistAllReconnectsConnectionWhenConnectionLost()
    {
        $this->mockPing->willReturn(false);
        $this->mockEntityManager->expects($this->exactly(1))->method('flush')->willReturn(null);

        $this->mockConnection->expects($this->at(0))->method('close');
        $this->mockConnection->expects($this->at(1))->method('connect');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function persistAllThrowsOriginalExceptionWhenEntityManagerGotClosed()
    {
        $this->mockEntityManager->expects($this->exactly(1))->method('flush')->willThrowException(new \Doctrine\DBAL\DBALException('Dummy error that closed the entity manager'));

        $this->mockConnection->expects($this->never())->method('close');
        $this->mockConnection->expects($this->never())->method('connect');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllCatchesConnectionExceptions()
    {
        $this->mockPing->willThrowException($this->getMockBuilder(ConnectionException::class)->disableOriginalConstructor()->getMock());
        $this->persistenceManager->persistAll();
    }
}
