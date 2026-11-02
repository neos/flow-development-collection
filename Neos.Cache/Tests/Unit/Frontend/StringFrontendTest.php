<?php

declare(strict_types=1);

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
use Neos\Cache\Backend\NullBackend;
use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the string cache frontend
 *
 */
final class StringFrontendTest extends BaseTestCase
{
    #[Test]
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->onlyMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->willReturn((false));
        $cache->set('foo', 'bar');
    }

    #[Test]
    public function setPassesStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('StringCacheTest'), self::equalTo($theString));

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString);
    }

    #[Test]
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->prepareDefaultBackend();

        $backend->expects($this->once())->method('set')->with(self::equalTo('StringCacheTest'), self::equalTo($theString), self::equalTo([]), self::equalTo($theLifetime));

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString, [], $theLifetime);
    }

    #[Test]
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $this->expectException(InvalidDataException::class);
        $backend = $this->prepareDefaultBackend();

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', []);
    }

    #[Test]
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();

        $backend->expects($this->once())->method('get')->willReturn(('Just some value'));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
    }

    #[Test]
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('has')->with(self::equalTo('StringCacheTest'))->willReturn((true));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertTrue($cache->has('StringCacheTest'), 'has() did not return true.');
    }

    #[Test]
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->prepareDefaultBackend();

        $backend->expects($this->once())->method('remove')->with(self::equalTo($cacheIdentifier))->willReturn((true));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertTrue($cache->remove($cacheIdentifier), 'remove() did not return true');
    }

    #[Test]
    public function getByTagRejectsInvalidTags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects($this->never())->method('findIdentifiersByTag');

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
    }

    #[Test]
    public function getByTagThrowAnExceptionWithoutTaggableBackend()
    {
        $this->expectException(NotSupportedByBackendException::class);
        $backend = $this->prepareDefaultBackend();
        $cache = new StringFrontend('VariableFrontend', $backend);
        $cache->getByTag('foo');
    }

    #[Test]
    public function getByTagCallsBackendAndReturnsIdentifiersAndValuesOfEntries()
    {
        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one' => 'one value', 'two' => 'two value'];
        $backend = $this->prepareTaggableBackend();

        $backend->expects($this->once())->method('findIdentifiersByTag')->with(self::equalTo($tag))->willReturn(($identifiers));
        $backend->expects($this->exactly(2))->method('get')->willReturnOnConsecutiveCalls('one value', 'two value');

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertSame($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @param array $methods
     * @return AbstractBackend|MockObject
     */
    protected function prepareDefaultBackend(array $methods = ['get', 'set', 'has', 'remove', 'flush', 'collectGarbage'])
    {
        return $this->getMockBuilder(AbstractBackend::class)
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $methods
     * @return AbstractBackend|MockObject
     */
    protected function prepareTaggableBackend(array $methods = ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
    {
        return $this->getMockBuilder(NullBackend::class)
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
