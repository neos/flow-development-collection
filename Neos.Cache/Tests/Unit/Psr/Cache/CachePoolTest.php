<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Psr\Cache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\Backend\AbstractBackend;
use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Psr\Cache\CacheItem;
use Neos\Cache\Psr\Cache\CachePool;
use Neos\Cache\Psr\InvalidArgumentException;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the PSR-6 cache frontend
 *
 */
final class CachePoolTest extends BaseTestCase
{
    public static function validIdentifiersDataProvider(): \Iterator
    {
        yield ['short'];
        yield ['SomeValidIdentifier'];
        yield ['withNumbers0123456789'];
        yield ['withUnder_score'];
        yield ['with.dot'];
        // The following tests exceed the minimum requirements of the PSR-6 keys (@see https://www.php-fig.org/psr/psr-6/#definitions)
        yield ['dashes-are-allowed'];
        yield ['percent%sign'];
        yield ['amper&sand'];
        yield ['a-string-that-exceeds-the-psr-minimum-maxlength-of-sixtyfour-but-is-shorter-than-twohundredandfifty-characters'];
    }

    #[DataProvider('validIdentifiersDataProvider')]
    #[Test]
    public function validIdentifiers(string $identifier): void
    {
        $mockBackend = $this->createStub(BackendInterface::class);
        $cachePool = new CachePool($identifier, $mockBackend);
        self::assertInstanceOf(CachePool::class, $cachePool);
    }

    public static function invalidIdentifiersDataProvider(): \Iterator
    {
        yield [''];
        yield ['späcialcharacters'];
        yield ['a-string-that-exceeds-the-maximum-allowed-length-of-twohundredandfifty-characters-which-is-pretty-large-as-it-turns-out-so-i-repeat-a-string-that-exceeds-the-maximum-allowed-length-of-twohundredandfifty-characters-still-not-there-wow-crazy-flow-rocks-though'];
    }

    #[DataProvider('invalidIdentifiersDataProvider')]
    #[Test]
    public function invalidIdentifiers(string $identifier): void
    {
        $mockBackend = $this->createStub(BackendInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        new CachePool($identifier, $mockBackend);
    }



    #[Test]
    public function getItemChecksIfTheIdentifierIsValid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @var CachePool|MockObject $cache */
        $cache = $this->getMockBuilder(CachePool::class)
            ->onlyMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->willReturn(false);
        $cache->getItem('foo');
    }

    #[Test]
    public function savePassesSerializedStringToBackend(): void
    {
        $theString = 'Just some value';
        $cacheItem = new CacheItem('PsrCacheTest', true, $theString);
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('PsrCacheTest'), self::equalTo(serialize($theString)));

        $cache = new CachePool('CachePool', $backend);
        $cache->save($cacheItem);
    }

    #[Test]
    public function savePassesSerializedArrayToBackend(): void
    {
        $theArray = ['Just some value', 'and another one.'];
        $cacheItem = new CacheItem('PsrCacheTest', true, $theArray);
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('PsrCacheTest'), self::equalTo(serialize($theArray)));

        $cache = new CachePool('CachePool', $backend);
        $cache->save($cacheItem);
    }

    #[Test]
    public function savePassesLifetimeToBackend(): void
    {
        // Note that this test can fail due to fraction of second problems in the calculation of lifetime vs. expiration date.
        $theString = 'Just some value';
        $theLifetime = 1234;
        $cacheItem = new CacheItem('PsrCacheTest', true, $theString);
        $cacheItem->expiresAfter($theLifetime);
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('PsrCacheTest'), self::equalTo(serialize($theString)), self::equalTo([]), self::equalTo($theLifetime, 1));

        $cache = new CachePool('CachePool', $backend);
        $cache->save($cacheItem);
    }

    #[Test]
    public function getItemFetchesValueFromBackend(): void
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->method('get')->willReturn(serialize($theString));

        $cache = new CachePool('CachePool', $backend);
        self::assertEquals(true, $cache->getItem('PsrCacheTest')->isHit(), 'The item should have been a hit but is not');
        self::assertEquals($theString, $cache->getItem('PsrCacheTest')->get(), 'The returned value was not the expected string.');
    }

    #[Test]
    public function getItemFetchesFalseBooleanValueFromBackend(): void
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->willReturn(serialize(false));

        $cache = new CachePool('CachePool', $backend);
        $retrievedItem = $cache->getItem('PsrCacheTest');
        self::assertEquals(true, $retrievedItem->isHit(), 'The item should have been a hit but is not');
        self::assertEquals(false, $retrievedItem->get(), 'The returned value was not the false.');
    }

    #[Test]
    public function hasItemReturnsResultFromBackend(): void
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('has')->with(self::equalTo('PsrCacheTest'))->willReturn(true);

        $cache = new CachePool('CachePool', $backend);
        self::assertTrue($cache->hasItem('PsrCacheTest'), 'hasItem() did not return true.');
    }

    #[Test]
    public function deleteItemCallsBackend(): void
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->prepareDefaultBackend();

        $backend->expects($this->once())->method('remove')->with(self::equalTo($cacheIdentifier))->willReturn(true);

        $cache = new CachePool('CachePool', $backend);
        self::assertTrue($cache->deleteItem($cacheIdentifier), 'deleteItem() did not return true');
    }

    /**
     * @return AbstractBackend|MockObject
     */
    protected function prepareDefaultBackend()
    {
        return $this->getMockBuilder(AbstractBackend::class)
            ->onlyMethods([
                'get',
                'set',
                'has',
                'remove',
                'flush',
                'collectGarbage'
            ])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
