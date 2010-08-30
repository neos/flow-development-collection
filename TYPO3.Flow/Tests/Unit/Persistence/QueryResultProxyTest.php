<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * Testcase for \F3\FLOW3\Persistence\QueryResultProxy
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class QueryResultProxyTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Persistence\QueryResultProxy
	 */
	protected $queryResultProxy;

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
		$this->query = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$this->queryResultProxy = new \F3\FLOW3\Persistence\QueryResultProxy($this->query);
		$this->sampleResult = array(array('foo' => 'Foo1', 'bar' => 'Bar1'), array('foo' => 'Foo2', 'bar' => 'Bar2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertType('F3\FLOW3\Persistence\QueryInterface', $this->queryResultProxy->getQuery());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getQueryReturnsAClone() {
		$this->assertNotSame($this->query, $this->queryResultProxy->getQuery());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetExistsWorksAsExpected() {
		$this->query->expects($this->once())->method('execute')->will($this->returnValue($this->sampleResult));
		$this->assertTrue($this->queryResultProxy->offsetExists(0));
		$this->assertFalse($this->queryResultProxy->offsetExists(2));
		$this->assertFalse($this->queryResultProxy->offsetExists('foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetGetWorksAsExpected() {
		$this->query->expects($this->once())->method('execute')->will($this->returnValue($this->sampleResult));
		$this->assertEquals(array('foo' => 'Foo1', 'bar' => 'Bar1'), $this->queryResultProxy->offsetGet(0));
		$this->assertNull($this->queryResultProxy->offsetGet(2));
		$this->assertNull($this->queryResultProxy->offsetGet('foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetSetWorksAsExpected() {
		$this->query->expects($this->once())->method('execute')->will($this->returnValue($this->sampleResult));
		$this->queryResultProxy->offsetSet(0, array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'));
		$this->assertEquals(array('foo' => 'FooOverridden', 'bar' => 'BarOverridden'), $this->queryResultProxy->offsetGet(0));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function offsetUnsetWorksAsExpected() {
		$this->query->expects($this->once())->method('execute')->will($this->returnValue($this->sampleResult));
		$this->queryResultProxy->offsetUnset(0);
		$this->assertFalse($this->queryResultProxy->offsetExists(0));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countDoesNotInitializeProxy() {
		$this->query->expects($this->never())->method('execute');
		$this->queryResultProxy->count();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function countCallsCountMethodOfQuery() {
		$this->query->expects($this->once())->method('count')->will($this->returnValue(123));
		$this->assertEquals(123, $this->queryResultProxy->count());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function iteratorMethodsAreCorrectlyImplemented() {
		$this->query->expects($this->once())->method('execute')->will($this->returnValue($this->sampleResult));
		$array1 = array('foo' => 'Foo1', 'bar' => 'Bar1');
		$array2 = array('foo' => 'Foo2', 'bar' => 'Bar2');
		$this->assertEquals($array1, $this->queryResultProxy->current());
		$this->assertTrue($this->queryResultProxy->valid());
		$this->queryResultProxy->next();
		$this->assertEquals($array2, $this->queryResultProxy->current());
		$this->assertTrue($this->queryResultProxy->valid());
		$this->assertEquals(1, $this->queryResultProxy->key());
		$this->queryResultProxy->next();
		$this->assertFalse($this->queryResultProxy->current());
		$this->assertFalse($this->queryResultProxy->valid());
		$this->assertNull($this->queryResultProxy->key());
		$this->queryResultProxy->rewind();
		$this->assertEquals(0, $this->queryResultProxy->key());
		$this->assertEquals($array1, $this->queryResultProxy->current());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function initializeExecutesQueryWithArrayFetchMode() {
		$queryResultProxy = $this->getAccessibleMock('F3\FLOW3\Persistence\QueryResultProxy', array('dummy'), array($this->query));
		$this->query->expects($this->once())->method('execute')->with(\F3\FLOW3\Persistence\QueryInterface::FETCH_ARRAY);
		$queryResultProxy->_call('initialize');
	}
}

?>