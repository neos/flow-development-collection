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
use Neos\Flow\Persistence;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for \Neos\Flow\Persistence\QueryResult
 *
 */
class QueryResultTest extends UnitTestCase
{
    /**
     * @var PersistenceManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistenceManager;

    /**
     * @var DataMapper|\PHPUnit_Framework_MockObject_MockObject
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
     * @var QueryResult|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sampleResult;

    /**
     * Sets up this test case
     *
     */
    public function setUp()
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManager::class)->disableOriginalConstructor()->getMock();
        $this->persistenceManager->expects($this->any())->method('getObjectDataByQuery')->will($this->returnValue(['one', 'two']));
        $this->dataMapper = $this->createMock(DataMapper::class);
        $this->query = $this->createMock(QueryInterface::class);
        $this->queryResult = new QueryResult($this->query);
        $this->queryResult->injectPersistenceManager($this->persistenceManager);
        $this->queryResult->injectDataMapper($this->dataMapper);
        $this->sampleResult = [['foo' => 'Foo1', 'bar' => 'Bar1'], ['foo' => 'Foo2', 'bar' => 'Bar2']];
        $this->dataMapper->expects($this->any())->method('mapToObjects')->will($this->returnValue($this->sampleResult));
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
    public function offsetExistsWorksAsExpected()
    {
        $this->assertTrue($this->queryResult->offsetExists(0));
        $this->assertFalse($this->queryResult->offsetExists(2));
        $this->assertFalse($this->queryResult->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function offsetGetWorksAsExpected()
    {
        $this->assertEquals(['foo' => 'Foo1', 'bar' => 'Bar1'], $this->queryResult->offsetGet(0));
        $this->assertNull($this->queryResult->offsetGet(2));
        $this->assertNull($this->queryResult->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function offsetSetWorksAsExpected()
    {
        $this->queryResult->offsetSet(0, ['foo' => 'FooOverridden', 'bar' => 'BarOverridden']);
        $this->assertEquals(['foo' => 'FooOverridden', 'bar' => 'BarOverridden'], $this->queryResult->offsetGet(0));
    }

    /**
     * @test
     */
    public function offsetUnsetWorksAsExpected()
    {
        $this->queryResult->offsetUnset(0);
        $this->assertFalse($this->queryResult->offsetExists(0));
    }

    /**
     * @test
     */
    public function countDoesNotInitializeProxy()
    {
        $queryResult = $this->getMockBuilder(QueryResult::class)->setMethods(['initialize'])->setConstructorArgs([$this->query])->getMock();
        $queryResult->injectPersistenceManager($this->persistenceManager);
        $queryResult->expects($this->never())->method('initialize');
        $queryResult->count();
    }

    /**
     * @test
     */
    public function countCallsGetObjectCountByQueryOnPersistenceManager()
    {
        $this->persistenceManager->expects($this->once())->method('getObjectCountByQuery')->will($this->returnValue(2));
        $this->assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countCountsQueryResultDirectlyIfAlreadyInitialized()
    {
        $this->persistenceManager->expects($this->never())->method('getObjectCountByQuery');
        $this->queryResult->toArray();
        $this->assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function countOnlyCallsGetObjectCountByQueryOnPersistenceManagerOnce()
    {
        $this->persistenceManager->expects($this->once())->method('getObjectCountByQuery')->will($this->returnValue(2));
        $this->queryResult->count();
        $this->assertEquals(2, $this->queryResult->count());
    }

    /**
     * @test
     */
    public function iteratorMethodsAreCorrectlyImplemented()
    {
        $array1 = ['foo' => 'Foo1', 'bar' => 'Bar1'];
        $array2 = ['foo' => 'Foo2', 'bar' => 'Bar2'];
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
    public function initializeExecutesQueryWithArrayFetchMode()
    {
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $queryResult->injectPersistenceManager($this->persistenceManager);
        $queryResult->injectDataMapper($this->dataMapper);
        $this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue(['FAKERESULT']));
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
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getFirstReturnsNullIfResultSetIsEmptyAndQueryIsInitialized()
    {
        $initializedQueryResult = [];
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $queryResult->_set('queryResult', $initializedQueryResult);

        $this->assertNull($queryResult->getFirst());
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
        $this->query->expects($this->once())->method('setLimit')->with(1);

        $queryResult->injectPersistenceManager($this->persistenceManager);

        $mockDataMapper = $this->createMock(DataMapper::class);
        $mockDataMapper->expects($this->once())->method('mapToObjects')->with(['one', 'two'])->will($this->returnValue($initializedQueryResult));
        $queryResult->injectDataMapper($mockDataMapper);

        $expectedResult = $initializedQueryResult[0];
        $actualResult = $queryResult->getFirst();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getFirstReturnsNullIfResultSetIsEmptyAndQueryIsNotInitialized()
    {
        $initializedQueryResult = [];
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [$this->query]);
        $this->query->expects($this->once())->method('setLimit')->with(1);

        $queryResult->injectPersistenceManager($this->persistenceManager);

        $mockDataMapper = $this->createMock(DataMapper::class);
        $mockDataMapper->expects($this->once())->method('mapToObjects')->with(['one', 'two'])->will($this->returnValue($initializedQueryResult));
        $queryResult->injectDataMapper($mockDataMapper);

        $this->assertNull($queryResult->getFirst());
    }
}
