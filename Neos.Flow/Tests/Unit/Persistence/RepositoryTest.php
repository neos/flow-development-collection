<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Persistence\RepositoryInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('Fixture/Repository/NonstandardEntityRepository.php');

/**
 * Testcase for the base Repository
 */
final class RepositoryTest extends UnitTestCase
{
    #[Test]
    public function abstractRepositoryImplementsRepositoryInterface()
    {
        $repository = $this->createStub(Repository::class);
        self::assertInstanceOf(RepositoryInterface::class, $repository);
    }

    /**
     * dataProvider for constructSetsObjectTypeFromClassName
     */
    public static function modelAndRepositoryClassNames(): \Iterator
    {
        $idSuffix = uniqid();
        yield ['TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\' . 'C' . $idSuffix . 'Blog'];
        yield ['Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\' . 'C' . $idSuffix . 'Page'];
        yield ['Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\' . 'C' . $idSuffix . 'Repository'];
    }

    #[DataProvider('modelAndRepositoryClassNames')]
    #[Test]
    public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName)
    {
        $mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
        eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \Neos\Flow\Persistence\Repository {}');

        $repository = new $mockClassName();
        self::assertEquals($modelClassName, $repository->getEntityClassName());
    }

    #[Test]
    public function constructSetsObjectTypeFromClassConstant()
    {
        $repositoryNamespace = 'Neos\Flow\Tests\Persistence\Fixture\Repository';
        $repositoryClassName = 'NonstandardEntityRepository';
        $modelClassName = 'Neos\Flow\Tests\Persistence\Fixture\Model\Entity';
        $fullRepositoryClassName = $repositoryNamespace . '\\' . $repositoryClassName;

        $repository = new $fullRepositoryClassName();
        self::assertSame($modelClassName, $repository->getEntityClassName());
    }

    #[Test]
    public function createQueryCallsPersistenceManagerWithExpectedClassName()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManager::class);
        $mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

        $repository = $this->getAccessibleMock(Repository::class, []);
        $repository->_set('entityClassName', 'ExpectedType');
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);

        $repository->createQuery();
    }

    #[Test]
    public function createQuerySetsDefaultOrderingIfDefined()
    {
        $orderings = ['foo' => QueryInterface::ORDER_ASCENDING];
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(PersistenceManager::class);
        $mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->willReturn(($mockQuery));

        $repository = $this->getAccessibleMock(Repository::class, []);
        $repository->_set('entityClassName', 'ExpectedType');
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->setDefaultOrderings($orderings);
        $repository->createQuery();

        $repository->setDefaultOrderings([]);
        $repository->createQuery();
    }

    #[Test]
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall()
    {
        $expectedResult = $this->createStub(QueryResultInterface::class);

        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('execute')->with()->willReturn(($expectedResult));

        $repository = $this->getMockBuilder(Repository::class)->onlyMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn(($mockQuery));

        self::assertSame($expectedResult, $repository->findAll());
    }

    #[Test]
    public function findByidentifierReturnsResultOfGetObjectByIdentifierCall()
    {
        $identifier = '123-456';
        $object = new \stdClass();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->willReturn(($object));

        $repository = $this->getAccessibleMock(Repository::class, ['createQuery']);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', 'stdClass');

        self::assertSame($object, $repository->findByIdentifier($identifier));
    }

    #[Test]
    public function addDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('add')->with($object);
        $repository = $this->getAccessibleMock(Repository::class, []);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->add($object);
    }

    #[Test]
    public function removeDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('remove')->with($object);
        $repository = $this->getAccessibleMock(Repository::class, []);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->remove($object);
    }

    #[Test]
    public function updateDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('update')->with($object);
        $repository = $this->getAccessibleMock(Repository::class, []);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->update($object);
    }

    #[Test]
    public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQueryResult = $this->createStub(QueryResultInterface::class);
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn(('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn(($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->with()->willReturn(($mockQueryResult));

        $repository = $this->getMockBuilder(Repository::class)->onlyMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn(($mockQuery));

        self::assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    #[Test]
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQueryResult->expects($this->once())->method('getFirst')->willReturn(($object));
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn(('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn(($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->willReturn(($mockQueryResult));

        $repository = $this->getMockBuilder(Repository::class)->onlyMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn(($mockQuery));

        self::assertSame($object, $repository->findOneByFoo('bar'));
    }

    #[Test]
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn(('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn(($mockQuery));
        $mockQuery->expects($this->once())->method('count')->willReturn((2));

        $repository = $this->getMockBuilder(Repository::class)->onlyMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn(($mockQuery));

        self::assertSame(2, $repository->countByFoo('bar'));
    }

    #[Test]
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled(): void
    {
        $this->expectExceptionMessage('Call to undefined method');
        $repository = $this->getMockBuilder(Repository::class)->onlyMethods(['createQuery'])->getMock();
        $repository->__call('foo', []);
    }

    #[Test]
    public function addChecksObjectType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $repository = $this->getAccessibleMock(Repository::class, []);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->add(new \stdClass());
    }

    #[Test]
    public function removeChecksObjectType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $repository = $this->getAccessibleMock(Repository::class, []);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->remove(new \stdClass());
    }
    #[Test]
    public function updateChecksObjectType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $repository = $this->getAccessibleMock(Repository::class, []);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }
}
