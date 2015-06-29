<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Persistence\Doctrine\QueryResult;
use TYPO3\Flow\Persistence\Doctrine\Query;

/**
 * Testcase for \TYPO3\Flow\Persistence\QueryResult
 *
 */
class QueryResultTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var QueryResult
	 */
	protected $queryResult;

	/**
	 * @var Query|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $query;

	/**
	 * Sets up this test case
	 *
	 */
	public function setUp() {
		$this->query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
		$this->query->expects($this->any())->method('getResult')->will($this->returnValue(array('First result', 'second result', 'third result')));
		$this->queryResult = new QueryResult($this->query);
	}

	/**
	 * @test
	 */
	public function getQueryReturnsQueryObject() {
		$this->assertInstanceOf('TYPO3\Flow\Persistence\QueryInterface', $this->queryResult->getQuery());
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
	public function offsetGetReturnsNullIfOffsetDoesNotExist() {
		$this->assertNull($this->queryResult->offsetGet('foo'));
	}

	/**
	 * @test
	 */
	public function countCallsCountOnTheQuery() {
		$this->query->expects($this->once())->method('count')->will($this->returnValue(123));
		$this->assertEquals(123, $this->queryResult->count());
	}

	/**
	 * @test
	 */
	public function countCountsQueryResultDirectlyIfAlreadyInitialized() {
		$this->query->expects($this->never())->method('count');
		$this->queryResult->toArray();
		$this->assertEquals(3, $this->queryResult->count());
	}

	/**
	 * @test
	 */
	public function countCallsCountOnTheQueryOnlyOnce() {
		$this->query->expects($this->once())->method('count')->will($this->returnValue(321));
		$this->queryResult->count();
		$this->assertEquals(321, $this->queryResult->count());
	}
}
