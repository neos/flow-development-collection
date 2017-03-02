<?php
namespace Neos\Flow\Tests\Unit\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\QueryResult;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for \Neos\Flow\Persistence\QueryResult
 */
class QueryResultTest extends UnitTestCase
{
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
    public function setUp()
    {
        $this->query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
        $this->query->expects($this->any())->method('getResult')->will($this->returnValue(['First result', 'second result', 'third result']));
        $this->queryResult = new QueryResult($this->query);
    }

    /**
     * @test
     */
    public function getQueryReturnsQueryObject()
    {
        $this->assertInstanceOf(QueryInterface::class, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function getQueryReturnsAClone()
    {
        $this->assertNotSame($this->query, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function offsetGetReturnsNullIfOffsetDoesNotExist()
    {
        $this->assertNull($this->queryResult->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function countCallsCountOnTheQuery()
    {
        $this->query->expects($this->once())->method('count')->will($this->returnValue(123));
        $this->assertEquals(123, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->query->expects($this->never())->method('count');
        $this->queryResult->toArray();
        $this->assertEquals(3, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCallsCountOnTheQueryOnlyOnce()
    {
        $this->query->expects($this->once())->method('count')->will($this->returnValue(321));
        $this->queryResult->count();
        $this->assertEquals(321, $this->queryResult->count());
    }
}
