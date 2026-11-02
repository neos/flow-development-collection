<?php

declare(strict_types=1);

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
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\Doctrine\QueryResult;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for \Neos\Flow\Persistence\QueryResult
 */
final class QueryResultTest extends UnitTestCase
{
    /**
     * @var QueryResult
     */
    protected $queryResult;

    /**
     * @var Query|MockObject
     */
    protected $query;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->query = $this->createMock(Query::class);
        $this->query->method('getResult')->willReturn((['First result', 'second result', 'third result']));
        $this->queryResult = new QueryResult($this->query);
    }

    #[Test]
    public function getQueryReturnsQueryObject()
    {
        self::assertInstanceOf(QueryInterface::class, $this->queryResult->getQuery());
    }

    #[Test]
    public function getQueryReturnsAClone()
    {
        self::assertNotSame($this->query, $this->queryResult->getQuery());
    }

    #[Test]
    public function offsetGetReturnsNullIfOffsetDoesNotExist()
    {
        self::assertNull($this->queryResult->offsetGet('foo'));
    }

    #[Test]
    public function countCallsCountOnTheQuery()
    {
        $this->query->expects($this->once())->method('count')->willReturn((123));
        self::assertCount(123, $this->queryResult);
    }

    #[Test]
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->query->expects($this->never())->method('count');
        $this->queryResult->toArray();
        self::assertCount(3, $this->queryResult);
    }

    #[Test]
    public function countCallsCountOnTheQueryOnlyOnce()
    {
        $this->query->expects($this->once())->method('count')->willReturn((321));
        $this->queryResult->count();
        self::assertCount(321, $this->queryResult);
    }
}
