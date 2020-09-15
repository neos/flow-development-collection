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
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Backend\TaggableBackendInterface;
use Neos\Cache\Frontend\StringFrontend;

/**
 * Testcase for the abstract cache frontend
 *
 */
class AbstractFrontendTest extends BaseTestCase
{
    /** @var  AbstractBackend */
    protected $mockBackend;

    public function setUp()
    {
        parent::setUp();
        $this->mockBackend = $this->getMockBuilder(AbstractBackend::class)->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function theConstructorAcceptsValidIdentifiers()
    {
        foreach (['x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('resource://some/äöü$&% sadf'), str_repeat('x', 250)] as $identifier) {
            $cache = new StringFrontend($identifier, $this->mockBackend);
            $this->assertInstanceOf(StringFrontend::class, $cache);
        }
    }

    /**
     * @test
     */
    public function theConstructorRejectsInvalidIdentifiers()
    {
        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $identifier) {
            try {
                new StringFrontend($identifier, $this->mockBackend);
                $this->fail('Identifier "' . $identifier . '" was not rejected.');
            } catch (\Exception $exception) {
                $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            }
        }
    }

    /**
     * @test
     */
    public function flushCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])->disableOriginalConstructor()->getMock();
        $backend->expects($this->once())->method('flush');

        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->flush();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function flushByTagRejectsInvalidTags()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects($this->never())->method('flushByTag');

        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->flushByTag('SomeInvalid\Tag');
    }

    /**
     * @test
     */
    public function flushByTagCallsBackendIfItIsATaggableBackend()
    {
        $tag = 'sometag';
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects($this->once())->method('flushByTag')->with($tag);

        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->flushByTag($tag);
    }

    /**
     * @test
     */
    public function collectGarbageCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMockBuilder(AbstractBackend::class)
            ->setMethods(['get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('collectGarbage');

        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();
        $cache->collectGarbage();
    }

    /**
     * @test
     */
    public function invalidEntryIdentifiersAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);

        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();

        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $entryIdentifier) {
            $this->assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validEntryIdentifiersAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();

        foreach (['_', 'abc-def', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)] as $entryIdentifier) {
            $this->assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
        }
    }

    /**
     * @test
     */
    public function invalidTagsAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();

        foreach (['', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#'] as $tag) {
            $this->assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validTagsAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->createMock(AbstractBackend::class);
        $cache = $this->getMockBuilder(StringFrontend::class)
            ->setMethods(['__construct', 'get', 'set', 'has', 'remove', 'getByTag'])
            ->setConstructorArgs([$identifier, $backend])
            ->getMock();

        foreach (['abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)] as $tag) {
            $this->assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
        }
    }
}
