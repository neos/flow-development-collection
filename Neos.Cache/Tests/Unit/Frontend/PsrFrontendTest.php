<?php
namespace Neos\Cache\Tests\Unit\Frontend;

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

use Neos\Cache\Backend\AbstractBackend;
use Neos\Cache\Frontend\PsrFrontend;
use Neos\Cache\Psr\PsrCacheItem;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Testcase for the PSR-6 cache frontend
 *
 */
class PsrFrontendTest extends BaseTestCase
{
    /**
     * @expectedException \Neos\Cache\Exception\PsrInvalidArgumentException
     * @test
     */
    public function getItemChecksIfTheIdentifierIsValid()
    {
        /** @var PsrFrontend|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->getMockBuilder(PsrFrontend::class)
            ->setMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects(self::once())->method('isValidEntryIdentifier')->with('foo')->willReturn(false);
        $cache->getItem('foo');
    }

    /**
     * @test
     */
    public function savePassesSerializedStringToBackend()
    {
        $theString = 'Just some value';
        $cacheItem = new PsrCacheItem('PsrCacheTest', true, $theString);
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('set')->with(self::equalTo('PsrCacheTest'), self::equalTo(serialize($theString)));

        $cache = new PsrFrontend('PsrFrontend', $backend);
        $cache->save($cacheItem);
    }

    /**
     * @test
     */
    public function savePassesSerializedArrayToBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $cacheItem = new PsrCacheItem('PsrCacheTest', true, $theArray);
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('set')->with(self::equalTo('PsrCacheTest'), self::equalTo(serialize($theArray)));

        $cache = new PsrFrontend('PsrFrontend', $backend);
        $cache->save($cacheItem);
    }

    /**
     * @test
     */
    public function savePassesLifetimeToBackend()
    {
        // Note that this test can fail due to fraction of second problems in the calculation of lifetime vs. expiration date.
        $theString = 'Just some value';
        $theLifetime = 1234;
        $cacheItem = new PsrCacheItem('PsrCacheTest', true, $theString);
        $cacheItem->expiresAfter($theLifetime);
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('set')->with(self::equalTo('PsrCacheTest'), self::equalTo(serialize($theString)), self::equalTo([]), self::equalTo($theLifetime, 1));

        $cache = new PsrFrontend('PsrFrontend', $backend);
        $cache->save($cacheItem);
    }

    /**
     * @test
     */
    public function getItemFetchesValueFromBackend()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::any())->method('get')->willReturn(serialize($theString));

        $cache = new PsrFrontend('PsrFrontend', $backend);
        self::assertEquals(true, $cache->getItem('PsrCacheTest')->isHit(), 'The item should have been a hit but is not');
        self::assertEquals($theString, $cache->getItem('PsrCacheTest')->get(), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function getItemFetchesFalseBooleanValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('get')->willReturn(serialize(false));

        $cache = new PsrFrontend('PsrFrontend', $backend);
        $retrievedItem = $cache->getItem('PsrCacheTest');
        self::assertEquals(true, $retrievedItem->isHit(), 'The item should have been a hit but is not');
        self::assertEquals(false, $retrievedItem->get(), 'The returned value was not the FALSE.');
    }

    /**
     * @test
     */
    public function hasItemReturnsResultFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('has')->with(self::equalTo('PsrCacheTest'))->willReturn(true);

        $cache = new PsrFrontend('PsrFrontend', $backend);
        self::assertTrue($cache->hasItem('PsrCacheTest'), 'hasItem() did not return TRUE.');
    }

    /**
     * @test
     */
    public function deleteItemCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->prepareDefaultBackend();

        $backend->expects(self::once())->method('remove')->with(self::equalTo($cacheIdentifier))->willReturn(true);

        $cache = new PsrFrontend('PsrFrontend', $backend);
        self::assertTrue($cache->deleteItem($cacheIdentifier), 'deleteItem() did not return TRUE');
    }

    /**
     * @return AbstractBackend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareDefaultBackend()
    {
        return $this->getMockBuilder(AbstractBackend::class)
            ->setMethods([
                'get',
                'set',
                'has',
                'remove',
                'findIdentifiersByTag',
                'flush',
                'flushByTag',
                'collectGarbage'
            ])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
