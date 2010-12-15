<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for \F3\FLOW3\Persistence\QueryResult
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class QueryResultTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\Persistence\QueryResult
	 */
	protected $queryResult;

	/**
	 * @var \F3\FLOW3\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->persistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->persistenceManager->expects($this->any())->method('getObjectDataByQuery')->will($this->returnValue(array('one', 'two')));
		$this->persistenceManager->expects($this->any())->method('getObjectCountByQuery')->will($this->returnValue(2));
		$this->dataMapper = $this->getMock('F3\FLOW3\Persistence\DataMapper');
		$this->query = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$this->queryResult = new \F3\FLOW3\Persistence\QueryResult($this->query);
		$this->queryResult->injectPersistenceManager($this->persistenceManager);
		$this->queryResult->injectDataMapper($this->dataMapper);
		$this->sampleResult = array(array('foo' => 'Foo1', 'bar' => 'Bar1'), array('foo' => 'Foo2', 'bar' => 'Bar2'));
		$this->dataMapper->expects($this->any())->method('mapToObjects')->will($this->returnValue($this->sampleResult));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertType('F3\FLOW3\Persistence\QueryInterface', $this->queryResult->getQuery());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->query, $this->queryResult->getQuery());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetExistsWorksAsExpected() {
		$this->assertTrue($this->queryResult->offsetExists(0));
		$this->assertFalse($this->queryResult->offsetExists(2));
		$this->assertFalse($this->queryResult->offsetExists('foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetGetWorksAsExpected() {
		$this->assertEquals(array('foo' => 'Foo1', 'bar' => 'Bar1'), $this->queryResult->offsetGet(0));
		$this->assertNull($this->queryResult->offsetGet(2));
		$this->assertNull($this->queryResult->offsetGet('foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetSetWorksAsExpected() {
		$this->queryResult->offsetSet(0, array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'));
		$this->assertEquals(array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'), $this->queryResult->offsetGet(0));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetUnsetWorksAsExpected() {
		$this->queryResult->offsetUnset(0);
		$this->assertFalse($this->queryResult->offsetExists(0));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countDoesNotInitializeProxy() {
		$queryResult = $this->getMock('F3\FLOW3\Persistence\QueryResult', array('initialize'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$queryResult->expects($this->never())->method('initialize');
		$queryResult->count();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countCallsGetObjectCountByQueryOnPersistenceManager() {
		$queryResult = $this->getMock('F3\FLOW3\Persistence\QueryResult', array('initialize'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$this->assertEquals(2, $queryResult->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function initializeExecutesQueryWithArrayFetchMode() {
		$queryResult = $this->getAccessibleMock('F3\FLOW3\Persistence\QueryResult', array('dummy'), array($this->query));
		$queryResult->injectPersistenceManager($this->persistenceManager);
		$queryResult->injectDataMapper($this->dataMapper);
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue(array('FAKERESULT')));
		$queryResult->_call('initialize');
	}
}

?>