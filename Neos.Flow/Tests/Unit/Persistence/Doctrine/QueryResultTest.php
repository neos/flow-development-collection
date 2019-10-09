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
     * @var Query|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $query;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->disableOriginalClone()->getMock();
        $this->query->expects(self::any())->method('getResult')->will(self::returnValue(['First result', 'second result', 'third result']));
        $this->queryResult = new QueryResult($this->query);
    }

    /**
     * @test
     */
    public function getQueryReturnsQueryObject()
    {
        self::assertInstanceOf(QueryInterface::class, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function getQueryReturnsAClone()
    {
        self::assertNotSame($this->query, $this->queryResult->getQuery());
    }

    /**
     * @test
     */
    public function offsetGetReturnsNullIfOffsetDoesNotExist()
    {
        self::assertNull($this->queryResult->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function countCallsCountOnTheQuery()
    {
        $this->query->expects(self::once())->method('count')->will(self::returnValue(123));
        self::assertEquals(123, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->query->expects(self::never())->method('count');
        $this->queryResult->toArray();
        self::assertEquals(3, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCallsCountOnTheQueryOnlyOnce()
    {
        $this->query->expects(self::once())->method('count')->will(self::returnValue(321));
        $this->queryResult->count();
        self::assertEquals(321, $this->queryResult->count());
    }
}
