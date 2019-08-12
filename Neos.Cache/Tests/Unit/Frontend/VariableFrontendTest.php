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
use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;

/**
 * Testcase for the variable cache frontend
 *
 */
class VariableFrontendTest extends BaseTestCase
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
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesSerializedStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesSerializedArrayToBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theArray);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)), $this->equalTo([]), $this->equalTo($theLifetime));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString, [], $theLifetime);
    }

    /**
     * @test
     * @requires extension igbinary
     */
    public function setUsesIgBinarySerializeIfAvailable()
    {
        $theString = 'Just some value';
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(igbinary_serialize($theString)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize('Just some value')));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals('Just some value', $cache->get('VariableCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function getFetchesArrayValueFromBackend()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function getFetchesFalseBooleanValueFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize(false)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertFalse($cache->get('VariableCacheTest'), 'The returned value was not the false.');
    }

    /**
     * @test
     * @requires extension igbinary
     */
    public function getUsesIgBinaryIfAvailable()
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('get')->will($this->returnValue(igbinary_serialize($theArray)));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        self::assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->prepareDefaultBackend();
        $backend->expects($this->once())->method('has')->with($this->equalTo('VariableCacheTest'))->will($this->returnValue(true));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->has('VariableCacheTest'), 'has() did not return true.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->prepareDefaultBackend();

        $backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(true));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->remove($cacheIdentifier), 'remove() did not return true');
    }

    /**
     * @test
     */
    public function getByTagRejectsInvalidTags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects($this->never())->method('findIdentifiersByTag');

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
    }

    /**
     * @test
     */
    public function getByTagThrowAnExceptionWithoutTaggableBackend()
    {
        $this->expectException(NotSupportedByBackendException::class);
        $backend = $this->prepareDefaultBackend();
        $cache = new VariableFrontend('VariableFrontend', $backend);
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

        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(serialize('one value'), serialize('two value')));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @test
     * @requires extension igbinary
     */
    public function getByTagUsesIgBinaryIfAvailable()
    {
        $tag = 'sometag';
        $identifiers = ['one', 'two'];
        $entries = ['one' => 'one value', 'two' => 'two value'];
        $backend = $this->prepareTaggableBackend();

        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(igbinary_serialize('one value'), igbinary_serialize('two value')));

        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        self::assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @return AbstractBackend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareDefaultBackend()
    {
        return $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $methods
     * @return AbstractBackend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareTaggableBackend(array $methods = ['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
    {
        return $this->getMockBuilder(NullBackend::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
