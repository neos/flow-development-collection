<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Cache\Backend\FileBackend;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the cache to file backend
 */
class FileBackendTest extends UnitTestCase
{
    /**
     */
    public function setUp()
    {
        vfsStream::setup('Foo');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception
     */
    public function setCacheThrowsExceptionOnNonWritableDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('http://localhost/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);

        $backend->setCache($mockCache);
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsToSetTheCurrentCacheDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');
        mkdir('vfs://Foo/OtherDirectory');

        $context = new ApplicationContext('Testing');
        $backend = new FileBackend($context, array('cacheDirectory' => 'vfs://Foo/OtherDirectory'));
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $this->assertEquals('vfs://Foo/OtherDirectory/', $backend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $this->assertEquals('vfs://Foo/Cache/Data/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches()
    {
        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);

        $frontend = new \TYPO3\Flow\Cache\Frontend\PhpFrontend('SomeCache', $backend);
        $frontend->initializeObject();

        $this->assertEquals('vfs://Foo/Cache/Code/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
     */
    public function setThrowsExceptionIfDataIsNotAString()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);
        $backend->set('SomeIdentifier', array('not a string'));
    }

    /**
     * @test
     */
    public function setReallySavesToTheSpecifiedDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);

        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, null, null, 0, strlen($data));
        $this->assertEquals($data, $retrievedData);
    }

    /**
     * @test
     */
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $data1 = 'some data' . microtime();
        $data2 = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data1, array(), 500);
        $backend->set($entryIdentifier, $data2, array(), 200);

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, null, null, 0, strlen($data2));
        $this->assertEquals($data2, $retrievedData);
    }

    /**
     * @test
     */
    public function setAlsoSavesSpecifiedTags()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, array('Tag1', 'Tag2'));

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, null, null, (strlen($data) + \TYPO3\Flow\Cache\Backend\FileBackend::EXPIRYTIME_LENGTH), 9);
        $this->assertEquals('Tag1 Tag2', $retrievedData);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception
     * @expectedExceptionCode 1248710426
     */
    public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength()
    {
        $cachePath = 'vfs://Foo';

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(5));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue($cachePath));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('setTag', 'writeCacheFile'), array(), '', false);
        $backend->expects($this->once())->method('writeCacheFile')->willReturn(false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCacheDirectory($cachePath);

        $backend->set($entryIdentifier, 'cache data');
    }

    /**
     * @test
     */
    public function setCacheDetectsAndLoadsAFrozenCache()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);

        $backend->freeze();

        unset($backend);

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $this->assertTrue($backend->isFrozen());
        $this->assertEquals($data, $backend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function getReturnsContentOfTheCorrectCacheFile()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('setTag'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data, array(), 500);

        $data = 'some other data' . microtime();
        $backend->set($entryIdentifier, $data, array(), 100);

        $loadedData = $backend->get($entryIdentifier);
        $this->assertEquals($data, $loadedData);
    }

    /**
     * @test
     */
    public function getReturnsFalseForExpiredEntries()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->expects($this->once())->method('isCacheFileExpired')->with('vfs://Foo/Cache/Data/UnitTestCache/ExpiredEntry')->will($this->returnValue(true));
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $this->assertFalse($backend->get('ExpiredEntry'));
    }

    /**
     * @test
     */
    public function getDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired');

        $backend->set('foo', 'some data');
        $backend->freeze();
        $this->assertEquals('some data', $backend->get('foo'));
        $this->assertFalse($backend->get('bar'));
    }

    /**
     * @test
     */
    public function hasReturnsTrueIfAnEntryExists()
    {
        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data);

        $this->assertTrue($backend->has($entryIdentifier), 'has() did not return TRUE.');
        $this->assertFalse($backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseForExpiredEntries()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(true, false));

        $this->assertFalse($backend->has('foo'));
        $this->assertTrue($backend->has('bar'));
    }

    /**
     * @test
     */
    public function hasDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $backend->set('foo', 'some data');
        $backend->freeze();
        $this->assertTrue($backend->has('foo'));
        $this->assertFalse($backend->has('bar'));
    }

    /**
     * @test
     *
     */
    public function removeReallyRemovesACacheEntry()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);
        $this->assertFileExists($pathAndFilename);

        $backend->remove($entryIdentifier);
        $this->assertFileNotExists($pathAndFilename);
    }

    /**
     */
    public function invalidEntryIdentifiers()
    {
        return array(
            'trailing slash' => array('/myIdentifer'),
            'trailing dot and slash' => array('./myIdentifer'),
            'trailing two dots and slash' => array('../myIdentifier'),
            'trailing with multiple dots and slashes' => array('.././../myIdentifier'),
            'slash in middle part' => array('my/Identifier'),
            'dot and slash in middle part' => array('my./Identifier'),
            'two dots and slash in middle part' => array('my../Identifier'),
            'multiple dots and slashes in middle part' => array('my.././../Identifier'),
            'pending slash' => array('myIdentifier/'),
            'pending dot and slash' => array('myIdentifier./'),
            'pending dots and slash' => array('myIdentifier../'),
            'pending multiple dots and slashes' => array('myIdentifier.././../'),
        );
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function setThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->set($identifier, 'cache data', array());
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $backend->get($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function hasThrowsExceptionForInvalidIdentifier($identifier)
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);

        $backend->has($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function removeThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $backend->remove($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function requireOnceThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $backend->requireOnce($identifier);
    }

    /**
     * @test
     */
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntry';

        $data = '<?php return "foo"; ?>';
        $backend->set($entryIdentifier, $data);

        $loadedData = $backend->requireOnce($entryIdentifier);
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     */
    public function requireOnceDoesNotCheckExpiryTimeIfBackendIsFrozen()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $data = '<?php return "foo"; ?>';
        $backend->set('FooEntry', $data);

        $backend->freeze();

        $loadedData = $backend->requireOnce('FooEntry');
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function requireOnceDoesNotSwallowExceptionsOfTheIncludedFile()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithException';
        $backend->set($entryIdentifier, '<?php throw new \Exception(); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function requireOnceDoesNotSwallowPhpWarningsOfTheIncludedFile()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithPhpWarning';
        $backend->set($entryIdentifier, '<?php trigger_error("Warning!", E_WARNING); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     * @expectedException \PHPUnit_Framework_Error_Notice
     */
    public function requireOnceDoesNotSwallowPhpNoticesOfTheIncludedFile()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithPhpNotice';
        $backend->set($entryIdentifier, '<?php $undefined ++; ?>');
        $backend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);

        $data = 'some data' . microtime();
        $backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
        $backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
        $backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

        $expectedEntry = 'BackendFileTest2';

        $actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
        $this->assertInternalType('array', $actualEntries);

        $this->assertEquals($expectedEntry, array_pop($actualEntries));
    }

    /**
     * @test
     */
    public function findIdentifiersByTagReturnsEmptyArrayForExpiredEntries()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
        $backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'), -100);
        $backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

        $this->assertSame(array(), $backend->findIdentifiersByTag('UnitTestTag%special'));
        $this->assertSame(array('BackendFileTest1', 'BackendFileTest3'), $backend->findIdentifiersByTag('UnitTestTag%test'));
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

        $backend->flush();

        $this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('findIdentifiersByTag', 'remove'), array(), '', false);

        $backend->expects($this->once())->method('findIdentifiersByTag')->with('UnitTestTag%special')->will($this->returnValue(array('foo', 'bar', 'baz')));
        $backend->expects($this->at(1))->method('remove')->with('foo');
        $backend->expects($this->at(2))->method('remove')->with('bar');
        $backend->expects($this->at(3))->method('remove')->with('baz');

        $backend->flushByTag('UnitTestTag%special');
    }

    /**
     * @test
     */
    public function collectGarbageRemovesExpiredCacheEntries()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('isCacheFileExpired'), array(), '', false);
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(true, false));
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

        $backend->collectGarbage();
        $this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     */
    public function flushUnfreezesTheCache()
    {
        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\AbstractFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\FileBackend::class, array('dummy'), array(), '', false);
        $backend->injectEnvironment($mockEnvironment);
        $backend->injectCacheManager($mockCacheManager);
        $backend->setCache($mockCache);
        $backend->freeze();

        $this->assertTrue($backend->isFrozen());
        $backend->flush();
        $this->assertFalse($backend->isFrozen());
    }

    /**
     * @test
     */
    public function backendAllowsForIteratingOverEntries()
    {
        $mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
        $mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

        $mockCacheManager = $this->getMock(\TYPO3\Flow\Cache\CacheManager::class, array(), array(), '', false);
        $mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $backend = new FileBackend(new ApplicationContext('Testing'));
        $backend->injectCacheManager($mockCacheManager);
        $backend->injectEnvironment($mockEnvironment);

        $cache = new VariableFrontend('UnitTestCache', $backend);
        $backend->setCache($cache);

        for ($i = 0; $i < 100; $i++) {
            $entryIdentifier = sprintf('entry-%s', $i);
            $data = 'some data ' . $i;
            $cache->set($entryIdentifier, $data);
        }

        $entries = array();
        foreach ($cache->getIterator() as $entryIdentifier => $data) {
            $entries[$entryIdentifier] = $data;
        }
        natsort($entries);
        $i = 0;
        foreach ($entries as $entryIdentifier => $data) {
            $this->assertEquals(sprintf('entry-%s', $i), $entryIdentifier);
            $this->assertEquals('some data ' . $i, $data);
            $i++;
        }
        $this->assertEquals(100, $i);
    }
}
