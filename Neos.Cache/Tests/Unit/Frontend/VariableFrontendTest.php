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
use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the variable cache frontend
 *
 */
final class VariableFrontendTest extends BaseTestCase
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
    public function setPassesSerializedStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(serialize($theString)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString);
    }

    #[Test]
    public function setPassesSerializedArrayToBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theArray);
    }

    #[Test]
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(serialize($theString)), self::equalTo([]), self::equalTo($theLifetime));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString, [], $theLifetime);
    }

    #[RequiresPhpExtension('igbinary')]
    #[Test]
    public function setUsesIgBinarySerializeIfAvailable()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with(self::equalTo('VariableCacheTest'), self::equalTo(igbinary_serialize($theString)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();
        $cache->set('VariableCacheTest', $theString);
    }

    #[Test]
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->willReturn((serialize('Just some value')));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals('Just some value', $cache->get('VariableCacheTest'), 'The returned value was not the expected string.');
    }

    #[Test]
    public function getFetchesArrayValueFromBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->willReturn((serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    #[Test]
    public function getFetchesFalseBooleanValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->willReturn((serialize(false)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertFalse($cache->get('VariableCacheTest'), 'The returned value was not the false.');
    }

    #[RequiresPhpExtension('igbinary')]
    #[Test]
    public function getUsesIgBinaryIfAvailable()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->willReturn((igbinary_serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        self::assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    #[Test]
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('has')->with(self::equalTo('VariableCacheTest'))->willReturn((true));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->has('VariableCacheTest'), 'has() did not return true.');
    }

    #[Test]
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->prepareDefaultBackend();

        $backend->expects($this->once())->method('remove')->with(self::equalTo($cacheIdentifier))->willReturn((true));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->remove($cacheIdentifier), 'remove() did not return true');
    }

    #[Test]
    public function getByTagRejectsInvalidTags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects($this->never())->method('findIdentifiersByTag');

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
    }

    #[Test]
    public function getByTagThrowAnExceptionWithoutTaggableBackend()
    {
        $this->expectException(NotSupportedByBackendException::class);
        $backend = $this->prepareDefaultBackend();
        $cache = new VariableFrontend('VariableFrontend', $backend);
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
        $backend->expects($this->exactly(2))->method('get')->willReturnOnConsecutiveCalls(serialize('one value'), serialize('two value'));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertSame($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    #[RequiresPhpExtension('igbinary')]
    #[Test]
    public function getByTagUsesIgBinaryIfAvailable()
    {
        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one' => 'one value', 'two' => 'two value'];
        $backend = $this->prepareTaggableBackend();

        $backend->expects($this->once())->method('findIdentifiersByTag')->with(self::equalTo($tag))->willReturn(($identifiers));
        $backend->expects($this->exactly(2))->method('get')->willReturnOnConsecutiveCalls(igbinary_serialize('one value'), igbinary_serialize('two value'));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        self::assertSame($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @return AbstractBackend|MockObject
     */
    protected function prepareDefaultBackend()
    {
        return $this->getMockBuilder(AbstractBackend::class)
            ->onlyMethods(['get', 'set', 'has', 'remove', 'flush', 'collectGarbage'])
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
