<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Persistence;

require_once('Fixture/Repository/NonstandardEntityRepository.php');

/**
 * Testcase for the base Repository
 */
class RepositoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function abstractRepositoryImplementsRepositoryInterface()
    {
        $repository = $this->createMock(Persistence\Repository::class);
        self::assertTrue($repository instanceof Persistence\RepositoryInterface);
    }

    /**
     * dataProvider for constructSetsObjectTypeFromClassName
     */
    public function modelAndRepositoryClassNames()
    {
        $idSuffix = uniqid();
        return [
            ['TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\' . 'C' . $idSuffix . 'Blog'],
            ['Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\' . 'C' . $idSuffix . 'Page'],
            ['Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\' . 'C' . $idSuffix . 'Repository']
        ];
    }

    /**
     * @test
     * @dataProvider modelAndRepositoryClassNames
     */
    public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName)
    {
        $mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
        eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \Neos\Flow\Persistence\Repository {}');

        $repository = new $mockClassName();
        self::assertEquals($modelClassName, $repository->getEntityClassName());
    }

    /**
     * @test
     */
    public function constructSetsObjectTypeFromClassConstant()
    {
        $repositoryNamespace = 'Neos\Flow\Tests\Persistence\Fixture\Repository';
        $repositoryClassName = 'NonstandardEntityRepository';
        $modelClassName = 'Neos\Flow\Tests\Persistence\Fixture\Model\Entity';
        $fullRepositoryClassName = $repositoryNamespace . '\\' . $repositoryClassName;

        $repository = new $fullRepositoryClassName();
        self::assertEquals($modelClassName, $repository->getEntityClassName());
    }

    /**
     * @test
     */
    public function createQueryCallsPersistenceManagerWithExpectedClassName()
    {
        $mockPersistenceManager = $this->createMock(Persistence\Doctrine\PersistenceManager::class);
        $mockPersistenceManager->expects(self::once())->method('createQueryForType')->with('ExpectedType');

        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedType');
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);

        $repository->createQuery();
    }

    /**
     * @test
     */
    public function createQuerySetsDefaultOrderingIfDefined()
    {
        $orderings = ['foo' => Persistence\QueryInterface::ORDER_ASCENDING];
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(Persistence\Doctrine\PersistenceManager::class);
        $mockPersistenceManager->expects(self::exactly(2))->method('createQueryForType')->with('ExpectedType')->will(self::returnValue($mockQuery));

        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedType');
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->setDefaultOrderings($orderings);
        $repository->createQuery();

        $repository->setDefaultOrderings([]);
        $repository->createQuery();
    }

    /**
     * @test
     */
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall()
    {
        $expectedResult = $this->createMock(Persistence\QueryResultInterface::class);

        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('execute')->with()->will(self::returnValue($expectedResult));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects(self::once())->method('createQuery')->will(self::returnValue($mockQuery));

        self::assertSame($expectedResult, $repository->findAll());
    }

    /**
     * @test
     */
    public function findByidentifierReturnsResultOfGetObjectByIdentifierCall()
    {
        $identifier = '123-456';
        $object = new \stdClass();

        $mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->will(self::returnValue($object));

        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['createQuery']);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', 'stdClass');

        self::assertSame($object, $repository->findByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function addDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('add')->with($object);
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->add($object);
    }

    /**
     * @test
     */
    public function removeDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('remove')->with($object);
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->remove($object);
    }

    /**
     * @test
     */
    public function updateDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('update')->with($object);
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->update($object);
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQueryResult = $this->createMock(Persistence\QueryResultInterface::class);
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->will(self::returnValue('matchCriteria'));
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->will(self::returnValue($mockQuery));
        $mockQuery->expects(self::once())->method('execute')->with()->will(self::returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects(self::once())->method('createQuery')->will(self::returnValue($mockQuery));

        self::assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(Persistence\QueryResultInterface::class);
        $mockQueryResult->expects(self::once())->method('getFirst')->will(self::returnValue($object));
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->will(self::returnValue('matchCriteria'));
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->will(self::returnValue($mockQuery));
        $mockQuery->expects(self::once())->method('execute')->will(self::returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects(self::once())->method('createQuery')->will(self::returnValue($mockQuery));

        self::assertSame($object, $repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->will(self::returnValue('matchCriteria'));
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->will(self::returnValue($mockQuery));
        $mockQuery->expects(self::once())->method('count')->will(self::returnValue(2));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects(self::once())->method('createQuery')->will(self::returnValue($mockQuery));

        self::assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled()
    {
        $this->expectError();
        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->__call('foo', []);
    }

    /**
     * @test
     */
    public function addChecksObjectType()
    {
        $this->expectException(Persistence\Exception\IllegalObjectTypeException::class);
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->add(new \stdClass());
    }

    /**
     * @test
     */
    public function removeChecksObjectType()
    {
        $this->expectException(Persistence\Exception\IllegalObjectTypeException::class);
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->remove(new \stdClass());
    }
    /**
     * @test
     */
    public function updateChecksObjectType()
    {
        $this->expectException(Persistence\Exception\IllegalObjectTypeException::class);
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }
}
