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
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Persistence\AllowedObjectsContainer;
use Neos\Flow\Persistence\Doctrine\AllowedObjectsListener;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Exception;
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
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockEntityManager;

    /**
     * @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockUnitOfWork;

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockConnection;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSystemLogger;

    /**
     * @var \PHPUnit_Framework_MockObject_InvocationMocker
     */
    protected $mockPing;

    protected function setUp(): void
    {
        $this->persistenceManager = $this->getMockBuilder(\Neos\Flow\Persistence\Doctrine\PersistenceManager::class)->setMethods(['emitAllObjectsPersisted'])->getMock();

        $this->mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->method('isOpen')->willReturn(true);
        $this->inject($this->persistenceManager, 'entityManager', $this->mockEntityManager);

        $this->mockUnitOfWork = $this->getMockBuilder(\Doctrine\ORM\UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->method('getUnitOfWork')->willReturn($this->mockUnitOfWork);

        $this->mockConnection = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)->disableOriginalConstructor()->getMock();
        $this->mockEntityManager->method('getConnection')->willReturn($this->mockConnection);

        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->inject($this->persistenceManager, 'logger', $this->mockSystemLogger);

        $mockThrowableStorage = $this->getMockBuilder(ThrowableStorageInterface::class)->getMock();
        $mockThrowableStorage->method('logThrowable')->willReturn('Exception got logged!');
        $this->inject($this->persistenceManager, 'throwableStorage', $mockThrowableStorage);

        $allowedObjectsContainer = new AllowedObjectsContainer();
        $this->inject($this->persistenceManager, 'allowedObjects', $allowedObjectsContainer);
        $allowedObjectsListener = $this->getMockBuilder(AllowedObjectsListener::class)->setMethods(['ping'])->getMock();
        $this->inject($allowedObjectsListener, 'allowedObjects', $allowedObjectsContainer);
        $this->inject($allowedObjectsListener, 'logger', $this->mockSystemLogger);
        $this->inject($allowedObjectsListener, 'throwableStorage', $mockThrowableStorage);
        $this->inject($allowedObjectsListener, 'persistenceManager', $this->persistenceManager);
        $this->mockEntityManager->method('flush')->willReturnCallback(function () use ($allowedObjectsListener) {
            $allowedObjectsListener->onFlush(new OnFlushEventArgs($this->mockEntityManager));
        });
        $this->mockPing = $allowedObjectsListener->method('ping')->withAnyParameters();
        $this->mockPing->willReturn(true);
    }

    /**
     * @test
     */
    public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier()
    {
        $entity = (object)[
            'Persistence_Object_Identifier' => null
        ];

        $this->mockEntityManager->method('contains')->with($entity)->willReturn(true);
        $this->mockUnitOfWork->method('getEntityIdentifier')->with($entity)->willReturn(['SomeIdentifier']);

        self::assertEquals('SomeIdentifier', $this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     */
    public function persistAllowedObjectsThrowsExceptionIfTryingToPersistNonAllowedObjects()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^Detected modified or new objects/');
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
        $this->mockUnitOfWork->method('getScheduledEntityUpdates')->willReturn($scheduledEntityUpdates);
        $this->mockUnitOfWork->method('getScheduledEntityDeletions')->willReturn($scheduledEntityDeletes);
        $this->mockUnitOfWork->method('getScheduledEntityInsertions')->willReturn($scheduledEntityInsertions);

        $this->persistenceManager->persistAllowedObjects();
    }

    /**
     * @test
     */
    public function persistAllowedObjectsRespectsObjectAllowed()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
        $this->mockUnitOfWork->method('getScheduledEntityUpdates')->willReturn($scheduledEntityUpdates);
        $this->mockUnitOfWork->method('getScheduledEntityDeletions')->willReturn($scheduledEntityDeletes);
        $this->mockUnitOfWork->method('getScheduledEntityInsertions')->willReturn($scheduledEntityInsertions);

        $this->mockEntityManager->expects(self::once())->method('flush');

        $this->persistenceManager->allowObject($mockObject);
        $this->persistenceManager->persistAllowedObjects();
    }

    /**
     * @test
     */
    public function persistAllAbortsIfConnectionIsClosed()
    {
        $mockEntityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $mockEntityManager->expects(self::atLeastOnce())->method('isOpen')->willReturn(false);
        $this->inject($this->persistenceManager, 'entityManager', $mockEntityManager);

        $mockEntityManager->expects(self::never())->method('flush');
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllEmitsAllObjectsPersistedSignal()
    {
        $this->mockEntityManager->expects(self::once())->method('flush');
        $this->persistenceManager->expects(self::once())->method('emitAllObjectsPersisted');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllReconnectsConnectionWhenConnectionLost()
    {
        $this->mockPing->willReturn(false);

        $this->mockConnection->expects(self::once())->method('close');
        $this->mockConnection->expects(self::once())->method('connect');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllThrowsOriginalExceptionWhenEntityManagerGotClosed()
    {
        $this->expectException(DBALException::class);
        $this->mockEntityManager->method('flush')->willThrowException(new DBALException('Dummy error that closed the entity manager'));

        $this->mockConnection->expects(self::never())->method('close');
        $this->mockConnection->expects(self::never())->method('connect');

        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function persistAllCatchesConnectionExceptions()
    {
        $this->mockConnection->method('connect')->withAnyParameters()->willThrowException(new ConnectionException());
        $this->persistenceManager->persistAll();
    }
}
