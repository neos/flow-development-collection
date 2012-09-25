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

/**
 * Testcase for \TYPO3\Flow\Persistence\QueryResult
 *
 */
class QueryResultTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Persistence\Doctrine\QueryResult
	 */
	protected $queryResult;

	/**
	 * @var \TYPO3\Flow\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * Sets up this test case
	 *
	 */
	public function setUp() {
		$this->query = $this->getMockBuilder('TYPO3\Flow\Persistence\Doctrine\Query')->disableOriginalConstructor()->disableOriginalClone()->getMock();
		$this->query->expects($this->any())->method('getResult')->will($this->returnValue(array()));
		$this->queryResult = new \TYPO3\Flow\Persistence\Doctrine\QueryResult($this->query);
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
}
?>