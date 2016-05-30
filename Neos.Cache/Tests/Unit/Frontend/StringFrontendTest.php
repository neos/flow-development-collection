<?php
namespace TYPO3\Flow\Cache\Tests\Unit\Frontend;

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
use TYPO3\Flow\Cache\Tests\BaseTestCase;

/**
 * Testcase for the string cache frontend
 *
 */
class StringFrontendTest extends BaseTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, ['isValidEntryIdentifier'], [], '', false);
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString));

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString), $this->equalTo([]), $this->equalTo($theLifetime));

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString, [], $theLifetime);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', []);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue('Just some value'));

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);
        $backend->expects($this->once())->method('has')->with($this->equalTo('StringCacheTest'))->will($this->returnValue(true));

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertTrue($cache->has('StringCacheTest'), 'has() did not return TRUE.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);

        $backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(true));

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getByTagRejectsInvalidTags()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\TaggableBackendInterface::class, [], [], '', false);
        $backend->expects($this->never())->method('findIdentifiersByTag');

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
    }

    /**
     * @test
     */
    public function getByTagCallsBackendAndReturnsIdentifiersAndValuesOfEntries()
    {
        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one' => 'one value', 'two' => 'two value'];
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'], [], '', false);

        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls('one value', 'two value'));

        $cache = new \TYPO3\Flow\Cache\Frontend\StringFrontend('StringFrontend', $backend);
        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }
}
