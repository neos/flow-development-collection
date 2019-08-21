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
use Neos\Cache\Backend\NullBackend;
use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Testcase for the string cache frontend
 *
 */
class StringFrontendTest extends BaseTestCase
{
    /**
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['isValidEntryIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects(self::once())->method('isValidEntryIdentifier')->with('foo')->will(self::returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('set')->with(self::equalTo('StringCacheTest'), self::equalTo($theString));

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->prepareDefaultBackend();

        $backend->expects(self::once())->method('set')->with(self::equalTo('StringCacheTest'), self::equalTo($theString), self::equalTo([]), self::equalTo($theLifetime));

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', $theString, [], $theLifetime);
    }

    /**
     * @test
     */
    public function setThrowsInvalidDataExceptionOnNonStringValues()
    {
        $this->expectException(InvalidDataException::class);
        $backend = $this->prepareDefaultBackend();

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->set('StringCacheTest', []);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();

        $backend->expects(self::once())->method('get')->will(self::returnValue('Just some value'));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects(self::once())->method('has')->with(self::equalTo('StringCacheTest'))->will(self::returnValue(true));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertTrue($cache->has('StringCacheTest'), 'has() did not return true.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->prepareDefaultBackend();

        $backend->expects(self::once())->method('remove')->with(self::equalTo($cacheIdentifier))->will(self::returnValue(true));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertTrue($cache->remove($cacheIdentifier), 'remove() did not return true');
    }

    /**
     * @test
     */
    public function getByTagRejectsInvalidTags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects(self::never())->method('findIdentifiersByTag');

        $cache = new StringFrontend('StringFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
    }

    /**
     * @test
     */
    public function getByTagThrowAnExceptionWithoutTaggableBackend()
    {
        $this->expectException(NotSupportedByBackendException::class);
        $backend = $this->prepareDefaultBackend();
        $cache = new StringFrontend('VariableFrontend', $backend);
        $cache->getByTag('foo');
    }

    /**
     * @test
     */
    public function getByTagCallsBackendAndReturnsIdentifiersAndValuesOfEntries()
    {
        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one' => 'one value', 'two' => 'two value'];
        $backend = $this->prepareTaggableBackend();

        $backend->expects(self::once())->method('findIdentifiersByTag')->with(self::equalTo($tag))->will(self::returnValue($identifiers));
        $backend->expects(self::exactly(2))->method('get')->will($this->onConsecutiveCalls('one value', 'two value'));

        $cache = new StringFrontend('StringFrontend', $backend);
        self::assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @param array $methods
     * @return AbstractBackend|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareDefaultBackend(array $methods = ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
    {
        return $this->getMockBuilder(AbstractBackend::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $methods
     * @return AbstractBackend|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareTaggableBackend(array $methods = ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
    {
        return $this->getMockBuilder(NullBackend::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
