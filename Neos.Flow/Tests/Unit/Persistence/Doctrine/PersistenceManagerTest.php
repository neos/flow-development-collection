<?php

declare(strict_types=1);

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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Persistence\AllowedObjectsContainer;
use Neos\Flow\Persistence\Doctrine\AllowedObjectsListener;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Exception;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the doctrine persistence manager
 */
final class PersistenceManagerTest extends UnitTestCase
{
    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var EntityManager|MockObject
     */
    protected $mockEntityManager;

    /**
     * @var UnitOfWork|MockObject
     */
    protected $mockUnitOfWork;

    /**
     * @var Connection|MockObject
     */
    protected $mockConnection;

    /**
     * @var \PHPUnit_Framework_MockObject_InvocationMocker
     */
    protected $mockPing;

    protected function setUp(): void
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManager::class)->onlyMethods(['emitAllObjectsPersisted'])->getMock();

        $this->mockEntityManager = $this->createMock(EntityManager::class);
        $this->mockEntityManager->method('isOpen')->willReturn(true);
        $this->inject($this->persistenceManager, 'entityManager', $this->mockEntityManager);

        $this->mockUnitOfWork = $this->createMock(UnitOfWork::class);
        $this->mockEntityManager->method('getUnitOfWork')->willReturn($this->mockUnitOfWork);

        $this->mockConnection = $this->createMock(Connection::class);
        $this->mockEntityManager->method('getConnection')->willReturn($this->mockConnection);

        $mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->inject($this->persistenceManager, 'logger', $mockSystemLogger);

        $mockThrowableStorage = $this->createMock(ThrowableStorageInterface::class);
        $mockThrowableStorage->method('logThrowable')->willReturn('Exception got logged!');
        $this->inject($this->persistenceManager, 'throwableStorage', $mockThrowableStorage);

        $allowedObjectsContainer = new AllowedObjectsContainer();
        $this->inject($this->persistenceManager, 'allowedObjects', $allowedObjectsContainer);
        $allowedObjectsListener = $this->getMockBuilder(AllowedObjectsListener::class)->onlyMethods(['ping'])->getMock();
        $this->inject($allowedObjectsListener, 'allowedObjects', $allowedObjectsContainer);
        $this->inject($allowedObjectsListener, 'logger', $mockSystemLogger);
        $this->inject($allowedObjectsListener, 'throwableStorage', $mockThrowableStorage);
        $this->inject($allowedObjectsListener, 'persistenceManager', $this->persistenceManager);
        $this->mockEntityManager->method('flush')->willReturnCallback(function () use ($allowedObjectsListener) {
            $allowedObjectsListener->onFlush(new OnFlushEventArgs($this->mockEntityManager));
        });
        $this->mockPing = $allowedObjectsListener->method('ping')->withAnyParameters();
        $this->mockPing->willReturn(true);
    }

    #[Test]
    public function getIdentifierByObjectUsesUnitOfWorkIdentityWithEmptyFlowPersistenceIdentifier()
    {
        $entity = (object)[
            'Persistence_Object_Identifier' => null
        ];

        $this->mockEntityManager->method('contains')->with($entity)->willReturn(true);
        $this->mockUnitOfWork->method('getEntityIdentifier')->with($entity)->willReturn(['SomeIdentifier']);

        self::assertEquals('SomeIdentifier', $this->persistenceManager->getIdentifierByObject($entity));
    }

    #[Test]
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

    #[Test]
    public function persistAllowedObjectsRespectsObjectAllowed()
    {
        $mockObject = new \stdClass();
        $scheduledEntityUpdates = [spl_object_hash($mockObject) => $mockObject];
        $scheduledEntityDeletes = [];
        $scheduledEntityInsertions = [];
        $this->mockUnitOfWork->method('getScheduledEntityUpdates')->willReturn($scheduledEntityUpdates);
        $this->mockUnitOfWork->method('getScheduledEntityDeletions')->willReturn($scheduledEntityDeletes);
        $this->mockUnitOfWork->method('getScheduledEntityInsertions')->willReturn($scheduledEntityInsertions);

        $this->mockEntityManager->expects($this->once())->method('flush');

        $this->persistenceManager->allowObject($mockObject);
        $this->persistenceManager->persistAllowedObjects();
    }

    #[Test]
    public function persistAllAbortsIfConnectionIsClosed()
    {
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->expects($this->atLeastOnce())->method('isOpen')->willReturn(false);
        $this->inject($this->persistenceManager, 'entityManager', $mockEntityManager);

        $mockEntityManager->expects($this->never())->method('flush');
        $this->persistenceManager->persistAll();
    }

    #[Test]
    public function persistAllEmitsAllObjectsPersistedSignal()
    {
        $this->mockEntityManager->expects($this->once())->method('flush');
        $this->persistenceManager->expects($this->once())->method('emitAllObjectsPersisted');

        $this->persistenceManager->persistAll();
    }

    #[Test]
    public function persistAllReconnectsConnectionWhenConnectionLost()
    {
        $this->mockPing->willReturn(false);

        $this->mockConnection->expects($this->once())->method('close');
        $this->mockConnection->expects($this->once())->method('connect');

        $this->persistenceManager->persistAll();
    }

    #[Test]
    public function persistAllThrowsOriginalExceptionWhenEntityManagerGotClosed()
    {
        $this->expectException(DBALException::class);
        $this->mockEntityManager->method('flush')->willThrowException(new DBALException('Dummy error that closed the entity manager'));

        $this->mockConnection->expects($this->never())->method('close');
        $this->mockConnection->expects($this->never())->method('connect');

        $this->persistenceManager->persistAll();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function persistAllCatchesConnectionExceptions()
    {
        $this->mockConnection->method('connect')->withAnyParameters()->willThrowException(new ConnectionException());
        $this->persistenceManager->persistAll();
    }
}
