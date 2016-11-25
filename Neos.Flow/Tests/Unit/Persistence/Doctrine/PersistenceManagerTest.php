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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Error as FlowError;

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
     * @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSystemLogger;

    public function setUp()
    {
        $this->persistenceManager = $this->getMockBuilder(\Neos\Flow\Persistence\Doctrine\PersistenceManager::class)->setMethods(['emitAllObjectsPersisted'])->getMock();

        $this->mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('isOpen')->will($this->returnValue(true));
        $this->inject($this->persistenceManager, 'entityManager', $this->mockEntityManager);

        $this->mockUnitOfWork = $this->getMockBuilder(\Doctrine\ORM\UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('getUnitOfWork')->will($this->returnValue($this->mockUnitOfWork));

        $this->mockConnection = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->expects($this->any())->method('getConnection')->will($this->returnValue($this->mockConnection));

        $this->mockSystemLogger = $this->createMock(\Neos\Flow\Log\SystemLoggerInterface::class);
        $this->inject($this->persistenceManager, 'systemLogger', $this->mockSystemLogger);
    }

    /**
     * @test
     */
    public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier()
    {
        $entity = (object)[
            'Persistence_Object_Identifier' => null
        ];

        $this->mockEntityManager->expects($this->any())->method('contains')->with($entity)->will($this->returnValue(true));
        $this->mockUnitOfWork->expects($this->any())->method('getEntityIdentifier')->with($entity)->will($this->returnValue(['SomeIdentifier']));

        $this->assertEquals('SomeIdentifier', $this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Persistence\Exception
     * @expectedExceptionMessageRegExp /^Detected modified or new objects/
     */
    public function persistAllThrowsExceptionIfTryingToPersistNonWhitelistedObjectsAndOnlyWhitelistedObjectsFlagIsTrue()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
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
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
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
     * @expectedException \Neos\Flow\Error\Exception
     */
    public function persistAllReconnectsConnectionOnFailure()
    {
        $this->mockEntityManager->expects($this->exactly(2))->method('flush')->will($this->throwException(new FlowError\Exception('Dummy connection error')));
        $this->mockConnection->expects($this->at(0))->method('close');
        $this->mockConnection->expects($this->at(1))->method('connect');

        $this->persistenceManager->persistAll();
    }
}
