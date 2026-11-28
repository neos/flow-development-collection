<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Backend;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;

include_once(__DIR__ . '/../../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\PdoBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the PDO cache backend
 */
#[RequiresPhpExtension('pdo_sqlite')]
final class PdoBackendTest extends BaseTestCase
{
    /**
     * @var string
     */
    protected $fixtureFolder;

    /**
     * @var string
     */
    protected $fixtureDB;

    #[Test]
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $this->expectException(Exception::class);
        $backend = new PdoBackend(new EnvironmentConfiguration('SomeApplication Testing', '/some/path', PHP_MAXPATHLEN));
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
    }

    #[Test]
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        self::assertTrue($backend->has($identifier));
    }

    #[Test]
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($data, $fetchedData);
    }

    #[Test]
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        self::assertFalse($backend->has($identifier));
    }

    #[Test]
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($otherData, $fetchedData);
    }

    #[Test]
    public function findIdentifiersByTagFindsSetEntries()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        self::assertEquals($entryIdentifier, $retrieved[0]);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals($entryIdentifier, $retrieved[0]);
    }

    #[Test]
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag3']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals([], $retrieved);
    }

    #[Test]
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        self::assertFalse($backend->has($identifier));
    }

    #[Test]
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        self::assertFalse($backend->remove($identifier));
    }

    #[Test]
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('PdoBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('PdoBackendTest3', $data, ['UnitTestTag%test']);

        $backend->flushByTag('UnitTestTag%special');

        self::assertTrue($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        self::assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        self::assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    #[Test]
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('PdoBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('PdoBackendTest3', $data, ['UnitTestTag%test']);

        $backend->flushByTags(['UnitTestTag%boring', 'UnitTestTag%special']);

        self::assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        self::assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        self::assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    #[Test]
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data);
        $backend->set('PdoBackendTest2', $data);
        $backend->set('PdoBackendTest3', $data);

        $backend->flush();

        self::assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        self::assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        self::assertFalse($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    #[Test]
    public function flushRemovesOnlyOwnEntries()
    {
        $thisCache = $this->createMock(FrontendInterface::class);
        $thisCache->method('getIdentifier')->willReturn(('thisCache'));
        $thisBackend = $this->setUpBackend();
        $thisBackend->setCache($thisCache);

        $thatCache = $this->createMock(FrontendInterface::class);
        $thatCache->method('getIdentifier')->willReturn(('thatCache'));
        $thatBackend = $this->setUpBackend();
        $thatBackend->setCache($thatCache);

        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();

        self::assertEquals('Hello', $thisBackend->get('thisEntry'));
        self::assertFalse($thatBackend->has('thatEntry'));
    }

    #[Test]
    public function iterationOverEmptyCacheYieldsNoData()
    {
        $backend = $this->setUpBackend();
        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }

    #[Test]
    public function iterationOverNotEmptyCacheYieldsData()
    {
        $backend = $this->setUpBackend();

        $backend->set('first', 'firstData');
        $backend->set('second', 'secondData');

        $data = \iterator_to_array($backend);
        self::assertSame(
            ['first' => 'firstData', 'second' => 'secondData'],
            $data
        );
    }

    #[Test]
    public function iterationResetsWhenDataIsSet()
    {
        $backend = $this->setUpBackend();

        $backend->set('first', 'firstData');
        $backend->set('second', 'secondData');
        \iterator_to_array($backend);

        $backend->set('third', 'thirdData');

        $data = \iterator_to_array($backend);
        self::assertSame(
            ['first' => 'firstData', 'second' => 'secondData', 'third' => 'thirdData'],
            $data
        );
    }

    #[Test]
    public function iterationResetsWhenDataFlushed()
    {
        $backend = $this->setUpBackend();

        $backend->set('first', 'firstData');
        \iterator_to_array($backend);

        $backend->flush();

        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }

    #[Test]
    public function iterationResetsWhenDataFlushedByTag()
    {
        $backend = $this->setUpBackend();

        $backend->set('first', 'firstData', ['tag']);
        \iterator_to_array($backend);

        $backend->flushByTag('tag');

        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }

    #[Test]
    public function iterationResetsWhenDataGetsRemoved()
    {
        $backend = $this->setUpBackend();

        $backend->set('first', 'firstData');
        \iterator_to_array($backend);

        $backend->remove('first');

        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }

    /**
     * Sets up the APC backend used for testing
     *
     * @return PdoBackend
     */
    #[Test]
    public function getRetriesIfConnectingFails(): void
    {
        $backend = $this->setUpBackendFailingToConnect(2, ['maximumRetries' => 3]);

        self::assertFalse($backend->get('foo'));
        self::assertSame([1, 2], $backend->retryDelays);
    }

    #[Test]
    public function hasThrowsLastExceptionIfMaximumRetriesAreExceeded(): void
    {
        $backend = $this->setUpBackendFailingToConnect(10, ['maximumRetries' => 2]);

        $this->expectException(Exception::class);
        try {
            $backend->has('foo');
        } finally {
            self::assertSame([1, 2], $backend->retryDelays);
        }
    }

    #[Test]
    public function getDoesNotRetryIfRetryOnErrorIsDisabled(): void
    {
        $backend = $this->setUpBackendFailingToConnect(1, ['retryOnError' => false]);

        $this->expectException(Exception::class);
        try {
            $backend->get('foo');
        } finally {
            self::assertSame([], $backend->retryDelays);
        }
    }

    #[Test]
    public function getDoesNotRetryOnErrorsUnrelatedToTheConnection(): void
    {
        $backend = $this->setUpBackendFailingToConnect(0);
        // connect, which creates the tables for sqlite, then switch to a table that does not exist
        $backend->has('foo');
        $backend->setTableName('missing_table');

        $this->expectException(\PDOException::class);
        try {
            $backend->get('foo');
        } finally {
            self::assertSame([], $backend->retryDelays);
        }
    }

    /**
     * A backend that fails to connect the given number of times and records retry delays instead of sleeping
     */
    protected function setUpBackendFailingToConnect(int $failingConnects, array $options = []): PdoBackend
    {
        $mockCache = $this->createMock(FrontendInterface::class);
        $mockCache->method('getIdentifier')->willReturn(('TestCache'));

        $backend = new class (new EnvironmentConfiguration('SomeApplication Testing', '/some/path', PHP_MAXPATHLEN), array_merge(['dataSourceName' => 'sqlite::memory:'], $options)) extends PdoBackend {
            public int $failingConnects = 0;
            public array $retryDelays = [];

            protected function connect()
            {
                if ($this->failingConnects > 0) {
                    $this->failingConnects--;
                    throw new Exception('Could not connect', 1334736164, new \PDOException('Connection refused'));
                }
                parent::connect();
            }

            public function setTableName(string $tableName): void
            {
                $this->cacheTableName = $tableName;
            }

            protected function waitBeforeRetry(int $retry): void
            {
                $this->retryDelays[] = $retry;
            }
        };
        $backend->failingConnects = $failingConnects;
        $backend->setCache($mockCache);

        return $backend;
    }

    protected function setUpBackend()
    {
        /** @var FrontendInterface|MockObject $mockCache */
        $mockCache = $this->createMock(FrontendInterface::class);
        $mockCache->method('getIdentifier')->willReturn(('TestCache'));

        $mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();

        $backend = new PdoBackend($mockEnvironmentConfiguration, ['dataSourceName' => 'sqlite::memory:']);
        $backend->setCache($mockCache);

        return $backend;
    }
}
