<?php
namespace TYPO3\Flow\Tests\Unit\Persistence;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once('Fixture/Repository/NonstandardEntityRepository.php');

/**
 * Testcase for the base Repository
 *
 */
class RepositoryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function abstractRepositoryImplementsRepositoryInterface()
    {
        $repository = $this->createMock(\TYPO3\Flow\Persistence\Repository::class);
        $this->assertTrue($repository instanceof \TYPO3\Flow\Persistence\RepositoryInterface);
    }

    /**
     * dataProvider for constructSetsObjectTypeFromClassName
     */
    public function modelAndRepositoryClassNames()
    {
        $idSuffix = uniqid();
        return array(
            array('TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\C' . $idSuffix . 'Blog'),
            array('Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\C' . $idSuffix . 'Page'),
            array('Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\C' . $idSuffix . 'Repository')
        );
    }

    /**
     * @test
     * @dataProvider modelAndRepositoryClassNames
     */
    public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName)
    {
        $mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
        eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \TYPO3\Flow\Persistence\Repository {}');

        $repository = new $mockClassName();
        $this->assertEquals($modelClassName, $repository->getEntityClassName());
    }

    /**
     * @test
     */
    public function constructSetsObjectTypeFromClassConstant()
    {
        $repositoryNamespace = \TYPO3\Flow\Tests\Persistence\Fixture\Repository::class;
        $repositoryClassName = 'NonstandardEntityRepository';
        $modelClassName = \TYPO3\Flow\Tests\Persistence\Fixture\Model\Entity::class;
        $fullRepositorClassName = $repositoryNamespace . '\\' . $repositoryClassName;

        $repository = new $fullRepositorClassName();
        $this->assertEquals($modelClassName, $repository->getEntityClassName());
    }

    /**
     * @test
     */
    public function createQueryCallsPersistenceManagerWithExpectedClassName()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
        $repository->_set('entityClassName', 'ExpectedType');
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);

        $repository->createQuery();
    }

    /**
     * @test
     */
    public function createQuerySetsDefaultOrderingIfDefined()
    {
        $orderings = array('foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);
        $mockQuery = $this->createMock(\TYPO3\Flow\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->will($this->returnValue($mockQuery));

        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
        $repository->_set('entityClassName', 'ExpectedType');
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->setDefaultOrderings($orderings);
        $repository->createQuery();

        $repository->setDefaultOrderings(array());
        $repository->createQuery();
    }

    /**
     * @test
     */
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall()
    {
        $expectedResult = $this->createMock(\TYPO3\Flow\Persistence\QueryResultInterface::class);

        $mockQuery = $this->createMock(\TYPO3\Flow\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

        $repository = $this->getMockBuilder(\TYPO3\Flow\Persistence\Repository::class)->setMethods(array('createQuery'))->getMock();
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

        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->will($this->returnValue($object));

        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('createQuery'));
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
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('add')->with($object);
        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
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
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('remove')->with($object);
        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
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
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('update')->with($object);
        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
        $this->inject($repository, 'persistenceManager', $mockPersistenceManager);
        $repository->_set('entityClassName', get_class($object));
        $repository->update($object);
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQueryResult = $this->createMock(\TYPO3\Flow\Persistence\QueryResultInterface::class);
        $mockQuery = $this->createMock(\TYPO3\Flow\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(\TYPO3\Flow\Persistence\Repository::class)->setMethods(array('createQuery'))->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(\TYPO3\Flow\Persistence\QueryResultInterface::class);
        $mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
        $mockQuery = $this->createMock(\TYPO3\Flow\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(\TYPO3\Flow\Persistence\Repository::class)->setMethods(array('createQuery'))->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($object, $repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQuery = $this->createMock(\TYPO3\Flow\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('count')->will($this->returnValue(2));

        $repository = $this->getMockBuilder(\TYPO3\Flow\Persistence\Repository::class)->setMethods(array('createQuery'))->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error
     */
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled()
    {
        $repository = $this->getMockBuilder(\TYPO3\Flow\Persistence\Repository::class)->setMethods(array('createQuery'))->getMock();
        $repository->__call('foo', array());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function addChecksObjectType()
    {
        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->add(new \stdClass());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function removeChecksObjectType()
    {
        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->remove(new \stdClass());
    }
    /**
     * @test
     * @expectedException \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function updateChecksObjectType()
    {
        $repository = $this->getAccessibleMock(\TYPO3\Flow\Persistence\Repository::class, array('dummy'));
        $repository->_set('entityClassName', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }
}
