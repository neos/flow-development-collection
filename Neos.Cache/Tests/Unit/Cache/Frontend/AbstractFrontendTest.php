<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Frontend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the abstract cache frontend
 *
 */
class AbstractFrontendTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorAcceptsValidIdentifiers()
    {
        $mockBackend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        foreach (array('x', 'someValue', '123fivesixseveneight', 'some&', 'ab_cd%', rawurlencode('resource://some/äöü$&% sadf'), str_repeat('x', 250)) as $identifier) {
            $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'), array($identifier, $mockBackend));
        }
        // dummy assertion to silence PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function theConstructorRejectsInvalidIdentifiers()
    {
        $mockBackend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $identifier) {
            try {
                $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag', 'flush', 'flushByTag', 'collectGarbage'), array($identifier, $mockBackend));
                $this->fail('Identifier "' . $identifier . '" was not rejected.');
            } catch (\InvalidArgumentException $exception) {
            }
        }
        // dummy assertion to silence PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function flushCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('flush');

        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        $cache->flush();
    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function flushByTagRejectsInvalidTags()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\TaggableBackendInterface::class);
        $backend->expects($this->never())->method('flushByTag');

        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        $cache->flushByTag('SomeInvalid\Tag');
    }

    /**
     * @test
     */
    public function flushByTagCallsBackendIfItIsATaggableBackend()
    {
        $tag = 'sometag';
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\TaggableBackendInterface::class);
        $backend->expects($this->once())->method('flushByTag')->with($tag);

        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        $cache->flushByTag($tag);
    }

    /**
     * @test
     */
    public function collectGarbageCallsBackend()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('collectGarbage');

        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        $cache->collectGarbage();
    }

    /**
     * @test
     */
    public function getClassTagRendersTagWhichCanBeUsedToTagACacheEntryWithACertainClass()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);

        $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        $this->assertEquals('%CLASS%TYPO3_Foo_Bar_Baz', \TYPO3\Flow\Cache\CacheManager::getClassTag('TYPO3\Foo\Bar\Baz'));
    }

    /**
     * @test
     */
    public function invalidEntryIdentifiersAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array(), array(), '', false);
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $entryIdentifier) {
            $this->assertFalse($cache->isValidEntryIdentifier($entryIdentifier), 'Invalid identifier "' . $entryIdentifier . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validEntryIdentifiersAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array(), array(), '', false);
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        foreach (array('_', 'abc-def', 'foo', 'bar123', '3some', '_bl_a', 'some&', 'one%TWO', str_repeat('x', 250)) as $entryIdentifier) {
            $this->assertTrue($cache->isValidEntryIdentifier($entryIdentifier), 'Valid identifier "' . $entryIdentifier . '" was not accepted.');
        }
    }

    /**
     * @test
     */
    public function invalidTagsAreRecognizedAsInvalid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array(), array(), '', false);
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#') as $tag) {
            $this->assertFalse($cache->isValidTag($tag), 'Invalid tag "' . $tag . '" was not rejected.');
        }
    }

    /**
     * @test
     */
    public function validTagsAreRecognizedAsValid()
    {
        $identifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array(), array(), '', false);
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('__construct', 'get', 'set', 'has', 'remove', 'getByTag'), array($identifier, $backend));
        foreach (array('abcdef', 'foo-bar', 'foo_baar', 'bar123', '3some', 'file%Thing', 'some&', '%x%', str_repeat('x', 250)) as $tag) {
            $this->assertTrue($cache->isValidTag($tag), 'Valid tag "' . $tag . '" was not accepted.');
        }
    }
}
