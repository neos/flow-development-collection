<?php
namespace Neos\Cache\Tests\Unit\Backend;

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

use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Tests\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use Neos\Cache\Frontend\AbstractFrontend;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Frontend\VariableFrontend;

/**
 * Test case for the cache to file backend
 */
class FileBackendTest extends BaseTestCase
{
    /**
     */
    protected function setUp(): void
    {
        vfsStream::setup('Foo');
    }

    /**
     * @test
     */
    public function setCacheThrowsExceptionOnNonWritableDirectory()
    {
        $this->expectException(Exception::class);
        $mockCache = $this->createMock(AbstractFrontend::class);

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([__DIR__ . '~Testing', 'http://localhost/', PHP_MAXPATHLEN]);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);

        $backend->setCache($mockCache);
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsToSetTheCurrentCacheDirectory()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::any())->method('getIdentifier')->will(self::returnValue('SomeCache'));

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            1024
        ]);

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');
        mkdir('vfs://Foo/OtherDirectory');

        $backend = new FileBackend($mockEnvironmentConfiguration, ['cacheDirectory' => 'vfs://Foo/OtherDirectory']);
        $backend->setCache($mockCache);

        self::assertEquals('vfs://Foo/OtherDirectory/', $backend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::any())->method('getIdentifier')->will(self::returnValue('SomeCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        self::assertEquals('vfs://Foo/Cache/Data/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches()
    {

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');

        $backend = $this->prepareDefaultBackend();
        $frontend = new PhpFrontend('SomeCache', $backend);
        $backend->setCache($frontend);

        self::assertEquals('vfs://Foo/Cache/Code/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function setReallySavesToTheSpecifiedDirectory()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);

        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, strlen($data));
        self::assertEquals($data, $retrievedData);
    }

    /**
     * @test
     */
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $data1 = 'some data' . microtime();
        $data2 = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data1, [], 500);
        $backend->set($entryIdentifier, $data2, [], 200);

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, strlen($data2));
        self::assertEquals($data2, $retrievedData);
    }

    /**
     * @test
     */
    public function setAlsoSavesSpecifiedTags()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, ['Tag1', 'Tag2']);

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, strlen($data), 9);
        self::assertEquals('Tag1 Tag2', $retrievedData);
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength()
    {
        $this->expectExceptionCode(1248710426);
        $this->expectException(Exception::class);
        $cachePath = 'vfs://Foo';

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            5
        ]);

        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['setTag', 'writeCacheFile'])
            ->disableOriginalConstructor()
            ->getMock();

        $backend->expects(self::once())->method('writeCacheFile')->willReturn(false);
        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCacheDirectory($cachePath);

        $backend->set($entryIdentifier, 'cache data');
    }

    /**
     * @test
     */
    public function setCacheDetectsAndLoadsAFrozenCache()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCache($mockCache);
        $backend->set($entryIdentifier, $data);
        $backend->freeze();
        unset($backend);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCache($mockCache);

        self::assertTrue($backend->isFrozen());
        self::assertEquals($data, $backend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function getReturnsContentOfTheCorrectCacheFile()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['setTag'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data, [], 500);

        $data = 'some other data' . microtime();
        $backend->set($entryIdentifier, $data, [], 100);

        $loadedData = $backend->get($entryIdentifier);
        self::assertEquals($data, $loadedData);
    }

    /**
     * @test
     */
    public function getReturnsFalseForExpiredEntries()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired'], [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $backend->expects(self::once())->method('isCacheFileExpired')->with('vfs://Foo/Cache/Data/UnitTestCache/ExpiredEntry')->will(self::returnValue(true));
        $backend->setCache($mockCache);

        self::assertFalse($backend->get('ExpiredEntry'));
    }

    /**
     * @test
     */
    public function getDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired'], [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $backend->setCache($mockCache);
        $backend->expects(self::once())->method('isCacheFileExpired');

        $backend->set('foo', 'some data');
        $backend->freeze();
        self::assertEquals('some data', $backend->get('foo'));
        self::assertFalse($backend->get('bar'));
    }

    /**
     * @test
     */
    public function hasReturnsTrueIfAnEntryExists()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data);

        self::assertTrue($backend->has($entryIdentifier), 'has() did not return true.');
        self::assertFalse($backend->has($entryIdentifier . 'Not'), 'has() did not return false.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseForExpiredEntries()
    {
        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->expects(self::exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(true, false));

        self::assertFalse($backend->has('foo'));
        self::assertTrue($backend->has('bar'));
    }

    /**
     * @test
     */
    public function hasDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired'], [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);
        $backend->setCache($mockCache);

        $backend->expects(self::once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $backend->set('foo', 'some data');
        $backend->freeze();
        self::assertTrue($backend->has('foo'));
        self::assertFalse($backend->has('bar'));
    }

    /**
     * @test
     *
     */
    public function removeReallyRemovesACacheEntry()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);
        self::assertFileExists($pathAndFilename);

        $backend->remove($entryIdentifier);
        self::assertFileDoesNotExist($pathAndFilename);
    }

    /**
     */
    public function invalidEntryIdentifiers()
    {
        return [
            'trailing slash' => ['/myIdentifer'],
            'trailing dot and slash' => ['./myIdentifer'],
            'trailing two dots and slash' => ['../myIdentifier'],
            'trailing with multiple dots and slashes' => ['.././../myIdentifier'],
            'slash in middle part' => ['my/Identifier'],
            'dot and slash in middle part' => ['my./Identifier'],
            'two dots and slash in middle part' => ['my../Identifier'],
            'multiple dots and slashes in middle part' => ['my.././../Identifier'],
            'pending slash' => ['myIdentifier/'],
            'pending dot and slash' => ['myIdentifier./'],
            'pending dots and slash' => ['myIdentifier../'],
            'pending multiple dots and slashes' => ['myIdentifier.././../'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     */
    public function setThrowsExceptionForInvalidIdentifier($identifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));
        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($identifier, 'cache data', []);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     */
    public function getThrowsExceptionForInvalidIdentifier($identifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->setCache($mockCache);

        $backend->get($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     */
    public function hasThrowsExceptionForInvalidIdentifier($identifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $backend = $this->prepareDefaultBackend(['dummy']);

        $backend->has($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     */
    public function removeThrowsExceptionForInvalidIdentifier($identifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->remove($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     */
    public function requireOnceThrowsExceptionForInvalidIdentifier($identifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->requireOnce($identifier);
    }

    /**
     * @test
     */
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntry';

        $data = '<?php return "foo"; ?>';
        $backend->set($entryIdentifier, $data);

        $loadedData = $backend->requireOnce($entryIdentifier);
        self::assertEquals('foo', $loadedData);
    }

    /**
     * @test
     */
    public function requireOnceDoesNotCheckExpiryTimeIfBackendIsFrozen()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->setCache($mockCache);

        $backend->expects(self::once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $data = '<?php return "foo"; ?>';
        $backend->set('FooEntry', $data);

        $backend->freeze();

        $loadedData = $backend->requireOnce('FooEntry');
        self::assertEquals('foo', $loadedData);
    }

    /**
     * @test
     */
    public function requireOnceDoesNotSwallowExceptionsOfTheIncludedFile()
    {
        $this->expectException(\Exception::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithException';
        $backend->set($entryIdentifier, '<?php throw new \Exception(); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     */
    public function requireOnceDoesNotSwallowPhpWarningsOfTheIncludedFile()
    {
        $this->expectWarning();
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithPhpWarning';
        $backend->set($entryIdentifier, '<?php trigger_error("Warning!", E_USER_WARNING); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     */
    public function requireOnceDoesNotSwallowPhpNoticesOfTheIncludedFile()
    {
        $this->expectNotice();
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithPhpNotice';
        $backend->set($entryIdentifier, '<?php trigger_error("Notice!", E_USER_NOTICE); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $data = 'some data' . microtime();
        $backend->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendFileTest3', $data, ['UnitTestTag%test']);

        $expectedEntry = 'BackendFileTest2';

        $actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
        self::assertIsArray($actualEntries);

        self::assertEquals($expectedEntry, array_pop($actualEntries));
    }

    /**
     * @test
     */
    public function findIdentifiersByTagReturnsEmptyArrayForExpiredEntries()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special'], -100);
        $backend->set('BackendFileTest3', $data, ['UnitTestTag%test']);

        self::assertSame([], $backend->findIdentifiersByTag('UnitTestTag%special'));
        self::assertSame(['BackendFileTest1', 'BackendFileTest3'], $backend->findIdentifiersByTag('UnitTestTag%test'));
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        self::assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        self::assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

        $backend->flush();

        self::assertFileDoesNotExist('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        self::assertFileDoesNotExist('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->prepareDefaultBackend(['findIdentifiersByTags', 'remove']);

        $backend->expects(self::once())->method('findIdentifiersByTags')->with(['UnitTestTag%special'])->will(self::returnValue(['foo', 'bar', 'baz']));
        $backend->expects(self::atLeast(3))->method('remove')->withConsecutive(['foo'], ['bar'], ['baz']);

        $backend->flushByTag('UnitTestTag%special');
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $backend = $this->prepareDefaultBackend(['findIdentifiersByTags', 'remove']);

        $backend->expects(self::once())->method('findIdentifiersByTags')->with(['UnitTestTag%special'])->will(self::returnValue(['foo', 'bar', 'baz']));
        $backend->expects(self::atLeast(3))->method('remove')->withConsecutive(['foo'], ['bar'], ['baz']);

        $backend->flushByTags(['UnitTestTag%special']);
    }

    /**
     * @test
     */
    public function collectGarbageRemovesExpiredCacheEntries()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->expects(self::exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(true, false));
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        self::assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        self::assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

        $backend->collectGarbage();
        self::assertFileDoesNotExist('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        self::assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     */
    public function flushUnfreezesTheCache()
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);
        $backend->freeze();

        self::assertTrue($backend->isFrozen());
        $backend->flush();
        self::assertFalse($backend->isFrozen());
    }

    /**
     * @test
     */
    public function backendAllowsForIteratingOverEntries()
    {
        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $backend = new FileBackend($mockEnvironmentConfiguration, []);

        $cache = new VariableFrontend('UnitTestCache', $backend);
        $backend->setCache($cache);

        for ($i = 0; $i < 100; $i++) {
            $entryIdentifier = sprintf('entry-%s', $i);
            $data = 'some data ' . $i;
            $cache->set($entryIdentifier, $data);
        }

        $entries = [];
        foreach ($cache->getIterator() as $entryIdentifier => $data) {
            $entries[$entryIdentifier] = $data;
        }
        natsort($entries);
        $i = 0;
        foreach ($entries as $entryIdentifier => $data) {
            self::assertEquals(sprintf('entry-%s', $i), $entryIdentifier);
            self::assertEquals('some data ' . $i, $data);
            $i++;
        }
        self::assertEquals(100, $i);
    }

    /**
     * @param array $backendMockMethods
     * @param array $environmentConfiguration
     * @return FileBackend
     */
    protected function prepareDefaultBackend($backendMockMethods = ['dummy'], array $environmentConfiguration = ['~Testing', 'vfs://Foo/', 255])
    {
        if ($environmentConfiguration[0][0] === '~') {
            $environmentConfiguration[0] = __DIR__ . $environmentConfiguration[0];
        }
        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock($environmentConfiguration);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods($backendMockMethods)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);

        return $backend;
    }

    /**
     * @param array $constructorArguments
     * @return EnvironmentConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createEnvironmentConfigurationMock(array $constructorArguments)
    {
        return $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs($constructorArguments)->setMethods(null)->getMock();
    }
}
