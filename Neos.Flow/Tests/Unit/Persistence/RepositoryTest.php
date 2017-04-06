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
use Neos\Flow\Tests\Persistence\Fixture;

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
        $this->assertTrue($repository instanceof Persistence\RepositoryInterface);
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
        $this->assertEquals($modelClassName, $repository->getEntityClassName());
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
        $this->assertEquals($modelClassName, $repository->getEntityClassName());
    }

    /**
     * @test
     */
    public function createQueryCallsPersistenceManagerWithExpectedClassName()
    {
        $mockPersistenceManager = $this->createMock(Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

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
        $mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->will($this->returnValue($mockQuery));

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
        $mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($expectedResult, $repository->findAll());
    }

    /**
     * @test
     */
    public function findByidentifierReturnsResultOfGetObjectByIdentifierCall()
    {
        $identifier = '123-456';
        $object = new \stdClass();

        $mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->will($this->returnValue($object));

        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['createQuery']);
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', 'stdClass');

        $this->assertSame($object, $repository->findByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function addDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('add')->with($object);
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
        $mockPersistenceManager->expects($this->once())->method('remove')->with($object);
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
        $mockPersistenceManager->expects($this->once())->method('update')->with($object);
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
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(Persistence\QueryResultInterface::class);
        $mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($object, $repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('count')->will($this->returnValue(2));

        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error
     */
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled()
    {
        $repository = $this->getMockBuilder(Persistence\Repository::class)->setMethods(['createQuery'])->getMock();
        $repository->__call('foo', []);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function addChecksObjectType()
    {
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->add(new \stdClass());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function removeChecksObjectType()
    {
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->remove(new \stdClass());
    }
    /**
     * @test
     * @expectedException \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function updateChecksObjectType()
    {
        $repository = $this->getAccessibleMock(Persistence\Repository::class, ['dummy']);
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }
}
