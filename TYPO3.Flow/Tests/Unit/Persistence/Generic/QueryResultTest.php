<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence\Generic;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for \TYPO3\FLOW3\Persistence\QueryResult
 *
 */
class QueryResultTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\QueryResult
	 */
	protected $queryResult;

	/**
	 * @var \TYPO3\FLOW3\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * Sets up this test case
	 *
	 */
	public function setUp() {
		$this->persistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\Generic\PersistenceManager', array(), array(), '', FALSE);
		$this->persistenceManager->expects($this->any())->method('getObjectDataByQuery')->will($this->returnValue(array('one', 'two')));
		$this->persistenceManager->expects($this->any())->method('getObjectCountByQuery')->will($this->returnValue(2));
		$this->dataMapper = $this->getMock('TYPO3\FLOW3\Persistence\Generic\DataMapper');
		$this->query = $this->getMock('TYPO3\FLOW3\Persistence\QueryInterface');
		$this->queryResult = new \TYPO3\FLOW3\Persistence\Generic\QueryResult($this->query);
		$this->queryResult->injectPersistenceManager($this->persistenceManager);
		$this->queryResult->injectDataMapper($this->dataMapper);
		$this->sampleResult = array(array('foo' => 'Foo1', 'bar' => 'Bar1'), array('foo' => 'Foo2', 'bar' => 'Bar2'));
		$this->dataMapper->expects($this->any())->method('mapToObjects')->will($this->returnValue($this->sampleResult));
	}

	/**
	 * @test
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertInstanceOf('TYPO3\FLOW3\Persistence\QueryInterface', $this->queryResult->getQuery());
	}

	/**
	 * @test
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->query, $this->queryResult->getQuery());
	}

	/**
	 * @test
	 */
	public function offsetExistsWorksAsExpected() {
		$this->assertTrue($this->queryResult->offsetExists(0));
		$this->assertFalse($this->queryResult->offsetExists(2));
		$this->assertFalse($this->queryResult->offsetExists('foo'));
	}

	/**
	 * @test
	 */
	public function offsetGetWorksAsExpected() {
		$this->assertEquals(array('foo' => 'Foo1', 'bar' => 'Bar1'), $this->queryResult->offsetGet(0));
		$this->assertNull($this->queryResult->offsetGet(2));
		$this->assertNull($this->queryResult->offsetGet('foo'));
	}

	/**
	 * @test
	 */
	public function offsetSetWorksAsExpected() {
		$this->queryResult->offsetSet(0, array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'));
		$this->assertEquals(array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'), $this->queryResult->offsetGet(0));
	}

	/**
	 * @test
	 */
	public function offsetUnsetWorksAsExpected() {
		$this->queryResult->offsetUnset(0);
		$this->assertFalse($this->queryResult->offsetExists(0));
	}

	/**
	 * @test
	 */
	public function countDoesNotInitializeProxy() {
		$queryResult = $this->getMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('initialize'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$queryResult->expects($this->never())->method('initialize');
		$queryResult->count();
	}

	/**
	 * @test
	 */
	public function countCallsGetObjectCountByQueryOnPersistenceManager() {
		$queryResult = $this->getMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('initialize'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$this->assertEquals(2, $queryResult->count());
	}

	/**
	 * @test
	 */
	public function iteratorMethodsAreCorrectlyImplemented() {
		$array1 = array('foo' => 'Foo1', 'bar' => 'Bar1');
		$array2 = array('foo' => 'Foo2', 'bar' => 'Bar2');
		$this->assertEquals($array1, $this->queryResult->current());
		$this->assertTrue($this->queryResult->valid());
		$this->queryResult->next();
		$this->assertEquals($array2, $this->queryResult->current());
		$this->assertTrue($this->queryResult->valid());
		$this->assertEquals(1, $this->queryResult->key());
		$this->queryResult->next();
		$this->assertFalse($this->queryResult->current());
		$this->assertFalse($this->queryResult->valid());
		$this->assertNull($this->queryResult->key());
		$this->queryResult->rewind();
		$this->assertEquals(0, $this->queryResult->key());
		$this->assertEquals($array1, $this->queryResult->current());
	}

	/**
	 * @test
	 */
	public function initializeExecutesQueryWithArrayFetchMode() {
		$queryResult = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('dummy'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$queryResult->injectDataMapper($this->dataMapper);
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue(array('FAKERESULT')));
		$queryResult->_call('initialize');
	}

	/**
	 * @test
	 */
	public function getFirstReturnsFirstResultIfQueryIsInitialized() {
		$initializedQueryResult = array(
			new \stdClass(),
			new \stdClass()
		);
		$queryResult = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('dummy'), array($this->query));
		$queryResult->_set('queryResult', $initializedQueryResult);

		$expectedResult = $initializedQueryResult[0];
		$actualResult = $queryResult->getFirst();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getFirstReturnsNullIfResultSetIsEmptyAndQueryIsInitialized() {
		$initializedQueryResult = array();
		$queryResult = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('dummy'), array($this->query));
		$queryResult->_set('queryResult', $initializedQueryResult);

		$this->assertNull($queryResult->getFirst());
	}

	/**
	 * @test
	 */
	public function getFirstMapsAndReturnsFirstResultIfQueryIsNotInitialized() {
		$initializedQueryResult = array(
			new \stdClass(),
			new \stdClass()
		);
		$queryResult = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('dummy'), array($this->query));
		$this->query->expects($this->once())->method('setLimit')->with(1);

		$queryResult->injectPersistenceManager($this->persistenceManager);

		$mockDataMapper = $this->getMock('TYPO3\FLOW3\Persistence\Generic\DataMapper');
		$mockDataMapper->expects($this->once())->method('mapToObjects')->with(array('one', 'two'))->will($this->returnValue($initializedQueryResult));
		$queryResult->injectDataMapper($mockDataMapper);

		$expectedResult = $initializedQueryResult[0];
		$actualResult = $queryResult->getFirst();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getFirstReturnsNullIfResultSetIsEmptyAndQueryIsNotInitialized() {
		$initializedQueryResult = array();
		$queryResult = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Generic\QueryResult', array('dummy'), array($this->query));
		$this->query->expects($this->once())->method('setLimit')->with(1);

		$queryResult->injectPersistenceManager($this->persistenceManager);

		$mockDataMapper = $this->getMock('TYPO3\FLOW3\Persistence\Generic\DataMapper');
		$mockDataMapper->expects($this->once())->method('mapToObjects')->with(array('one', 'two'))->will($this->returnValue($initializedQueryResult));
		$queryResult->injectDataMapper($mockDataMapper);

		$this->assertNull($queryResult->getFirst());
	}

}

?>