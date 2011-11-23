<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('Fixture/Repository/NonstandardEntityRepository.php');

/**
 * Testcase for the base Repository
 *
 */
class RepositoryTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$repository = new \TYPO3\FLOW3\Persistence\Repository;
		$this->assertTrue($repository instanceof \TYPO3\FLOW3\Persistence\RepositoryInterface);
	}

	/**
	 * dataProvider for constructSetsObjectTypeFromClassName
	 */
	public function modelAndRepositoryClassNames() {
		$idSuffix = uniqid();
		return array(
			array('TYPO3\Blog\Domain\Repository', 'C' . $idSuffix . 'BlogRepository', 'TYPO3\Blog\Domain\Model\\' . 'C' . $idSuffix . 'Blog'),
			array('Domain\Repository\Content', 'C' . $idSuffix . 'PageRepository', 'Domain\Model\\Content\\' . 'C' . $idSuffix . 'Page'),
			array('Domain\Repository', 'C' . $idSuffix . 'RepositoryRepository', 'Domain\Model\\' . 'C' . $idSuffix . 'Repository')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 */
	public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName) {
		$mockClassName = $repositoryNamespace . '\\' . $repositoryClassName;
		eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . ' extends \TYPO3\FLOW3\Persistence\Repository {}');

		$repository = new $mockClassName();
		$this->assertEquals($modelClassName, $repository->getEntityClassName());
	}

	/**
	 * @test
	 */
	public function constructSetsObjectTypeFromClassConstant() {
		$repositoryNamespace = 'TYPO3\FLOW3\Tests\Persistence\Fixture\Repository';
		$repositoryClassName = 'NonstandardEntityRepository';
		$modelClassName = 'TYPO3\FLOW3\Tests\Persistence\Fixture\Model\Entity';
		$fullRepositorClassName = $repositoryNamespace . '\\' . $repositoryClassName;

		$repository = new $fullRepositorClassName();
		$this->assertEquals($modelClassName, $repository->getEntityClassName());
	}

	/**
	 * @test
	 */
	public function createQueryCallsPersistenceManagerWithExpectedClassName() {
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\Generic\PersistenceManager');
		$mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('entityClassName', 'ExpectedType');
		$repository->injectPersistenceManager($mockPersistenceManager);

		$repository->createQuery();
	}

	/**
	 * @test
	 */
	public function createQuerySetsDefaultOrderingIfDefined() {
		$orderings = array('foo' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING);
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\Generic\PersistenceManager');
		$mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->will($this->returnValue($mockQuery));

		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('entityClassName', 'ExpectedType');
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->setDefaultOrderings($orderings);
		$repository->createQuery();

		$repository->setDefaultOrderings(array());
		$repository->createQuery();
	}

	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('TYPO3\FLOW3\Persistence\QueryResultInterface');

		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

		$repository = $this->getMock('TYPO3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($expectedResult, $repository->findAll());
	}

	/**
	 * @test
	 */
	public function findByidentifierReturnsResultOfGetObjectByIdentifierCall() {
		$identifier = '123-456';
		$object = new \stdClass();

		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->will($this->returnValue($object));

		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('entityClassName', 'stdClass');

		$this->assertSame($object, $repository->findByIdentifier($identifier));
	}

	/**
	 * @test
	 */
	public function addDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('add')->with($object);
		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('entityClassName', get_class($object));
		$repository->add($object);
	}

	/**
	 * @test
	 */
	public function removeDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('remove')->with($object);
		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('entityClassName', get_class($object));
		$repository->remove($object);
	}

	/**
	 * @test
	 */
	public function updateDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('update')->with($object);
		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('entityClassName', get_class($object));
		$repository->update($object);
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('TYPO3\FLOW3\Persistence\QueryResultInterface');
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('TYPO3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new \stdClass();
		$mockQueryResult = $this->getMock('TYPO3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('TYPO3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($object, $repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQuery = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('count')->will($this->returnValue(2));

		$repository = $this->getMock('TYPO3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame(2, $repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$repository = $this->getMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 */
	public function addChecksObjectType() {
		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('entityClassName', 'ExpectedObjectType');

		$repository->add(new \stdClass());
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeChecksObjectType() {
		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('entityClassName', 'ExpectedObjectType');

		$repository->remove(new \stdClass());
	}
	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 */
	public function updateChecksObjectType() {
		$repository = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('entityClassName', 'ExpectedObjectType');

		$repository->update(new \stdClass());
	}
}

?>
