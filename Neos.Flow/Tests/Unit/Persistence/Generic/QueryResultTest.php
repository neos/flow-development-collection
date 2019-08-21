<?php
namespace Neos\Flow\Tests\Unit\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Generic\PersistenceManager;
use Neos\Flow\Persistence\Generic\DataMapper;
use Neos\Flow\Persistence\Generic\QueryResult;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for \Neos\Flow\Persistence\QueryResult
 *
 */
class QueryResultTest extends UnitTestCase
{
    /**
     * @var PersistenceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $persistenceManager;

    /**
     * @var DataMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataMapper;

    /**
     * @var QueryResult
     */
    protected $queryResult;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var QueryResult|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sampleResult;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManager::class)->disableOriginalConstructor()->getMock();
        $this->persistenceManager->expects(self::any())->method('getObjectDataByQuery')->will(self::returnValue(['one', 'two']));
        $this->dataMapper = $this->createMock(DataMapper::class);
        $this->query = $this->createMock(QueryInterface::class);
        $this->queryResult = new QueryResult($this->query);
        $this->queryResult->injectPersistenceManager($this->persistenceManager);
        $this->queryResult->injectDataMapper($this->dataMapper);
        $this->sampleResult = [['foo' => 'Foo1', 'bar' => 'Bar1'], ['foo' => 'Foo2', 'bar' => 'Bar2']];
        $this->dataMapper->expects(self::any())->method('mapToObjects')->will(self::returnValue($this->sampleResult));
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
    public function offsetExistsWorksAsExpected()
    {
        self::assertTrue($this->queryResult->offsetExists(0));
        self::assertFalse($this->queryResult->offsetExists(2));
        self::assertFalse($this->queryResult->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function offsetGetWorksAsExpected()
    {
        self::assertEquals(['foo' => 'Foo1', 'bar' => 'Bar1'], $this->queryResult->offsetGet(0));
        self::assertNull($this->queryResult->offsetGet(2));
        self::assertNull($this->queryResult->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function offsetSetWorksAsExpected()
    {
        $this->queryResult->offsetSet(0, ['foo' => 'FooOverridden', 'bar' => 'BarOverridden']);
        self::assertEquals(['foo' => 'FooOverridden', 'bar' => 'BarOverridden'], $this->queryResult->offsetGet(0));
    }

    /**
     * @test
     */
    public function offsetUnsetWorksAsExpected()
    {
        $this->queryResult->offsetUnset(0);
        self::assertFalse($this->queryResult->offsetExists(0));
    }

    /**
     * @test
     */
    public function countDoesNotInitializeProxy()
    {
        $queryResult = $this->getMockBuilder(QueryResult::class)->setMethods(['initialize'])->setConstructorArgs([$this->query])->getMock();
        $queryResult->injectPersistenceManager($this->persistenceManager);
        $queryResult->expects(self::never())->method('initialize');
        $queryResult->count();
    }

    /**
     * @test
     */
    public function countCallsGetObjectCountByQueryOnPersistenceManager()
    {
        $this->persistenceManager->expects(self::once())->method('getObjectCountByQuery')->will(self::returnValue(2));
        self::assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->persistenceManager->expects(self::never())->method('getObjectCountByQuery');
        $this->queryResult->toArray();
        self::assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countOnlyCallsGetObjectCountByQueryOnPersistenceManagerOnce()
    {
        $this->persistenceManager->expects(self::once())->method('getObjectCountByQuery')->will(self::returnValue(2));
        $this->queryResult->count();
        self::assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function iteratorMethodsAreCorrectlyImplemented()
    {
        $array1 = ['foo' => 'Foo1', 'bar' => 'Bar1'];
        $array2 = ['foo' => 'Foo2', 'bar' => 'Bar2'];
        self::assertEquals($array1, $this->queryResult->current());
        self::assertTrue($this->queryResult->valid());
        $this->queryResult->next();
        self::assertEquals($array2, $this->queryResult->current());
        self::assertTrue($this->queryResult->valid());
        self::assertEquals(1, $this->queryResult->key());
        $this->queryResult->next();
        self::assertFalse($this->queryResult->current());
        self::assertFalse($this->queryResult->valid());
        self::assertNull($this->queryResult->key());
        $this->queryResult->rewind();
        self::assertEquals(0, $this->queryResult->key());
        self::assertEquals($array1, $this->queryResult->current());
    }

    /**
     * @test
     */
    public function initializeExecutesQueryWithArrayFetchMode()
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $queryResult->injectPersistenceManager($this->persistenceManager);
        $queryResult->injectDataMapper($this->dataMapper);
        $this->persistenceManager->expects(self::once())->method('getObjectDataByQuery')->with($this->query)->will(self::returnValue(['FAKERESULT']));
        $queryResult->_call('initialize');
    }

    /**
     * @test
     */
    public function getFirstReturnsFirstResultIfQueryIsInitialized()
    {
        $initializedQueryResult = [
            new \stdClass(),
            new \stdClass()
        ];
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $queryResult->_set('queryResult', $initializedQueryResult);

        $expectedResult = $initializedQueryResult[0];
        $actualResult = $queryResult->getFirst();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getFirstReturnsNullIfResultSetIsEmptyAndQueryIsInitialized()
    {
        $initializedQueryResult = [];
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $queryResult->_set('queryResult', $initializedQueryResult);

        self::assertNull($queryResult->getFirst());
    }

    /**
     * @test
     */
    public function getFirstMapsAndReturnsFirstResultIfQueryIsNotInitialized()
    {
        $initializedQueryResult = [
            new \stdClass(),
            new \stdClass()
        ];
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $this->query->expects(self::once())->method('setLimit')->with(1);

        $queryResult->injectPersistenceManager($this->persistenceManager);

        $mockDataMapper = $this->createMock(DataMapper::class);
        $mockDataMapper->expects(self::once())->method('mapToObjects')->with(['one', 'two'])->will(self::returnValue($initializedQueryResult));
        $queryResult->injectDataMapper($mockDataMapper);

        $expectedResult = $initializedQueryResult[0];
        $actualResult = $queryResult->getFirst();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getFirstReturnsNullIfResultSetIsEmptyAndQueryIsNotInitialized()
    {
        $initializedQueryResult = [];
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $this->query->expects(self::once())->method('setLimit')->with(1);

        $queryResult->injectPersistenceManager($this->persistenceManager);

        $mockDataMapper = $this->createMock(DataMapper::class);
        $mockDataMapper->expects(self::once())->method('mapToObjects')->with(['one', 'two'])->will(self::returnValue($initializedQueryResult));
        $queryResult->injectDataMapper($mockDataMapper);

        self::assertNull($queryResult->getFirst());
    }
}
