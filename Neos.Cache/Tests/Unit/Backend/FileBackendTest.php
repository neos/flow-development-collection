<?php

declare(strict_types=1);

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
use Neos\Cache\Backend\FileBackendEntryDto;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\AbstractFrontend;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Cache\Tests\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for the cache to file backend
 */
final class FileBackendTest extends BaseTestCase
{
    /**
     */
    protected function setUp(): void
    {
        vfsStream::setup('Foo');
    }

    #[Test]
    public function setCacheThrowsExceptionOnNonWritableDirectory(): void
    {
        $this->expectException(Exception::class);
        $mockCache = $this->createStub(AbstractFrontend::class);

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([__DIR__ . '~Testing', 'http://localhost/', PHP_MAXPATHLEN]);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);

        $backend->setCache($mockCache);
    }

    #[Test]
    public function setCacheDirectoryAllowsToSetTheCurrentCacheDirectory(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->method('getIdentifier')->willReturn(('SomeCache'));

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

        self::assertSame('vfs://Foo/OtherDirectory/', $backend->getCacheDirectory());
    }

    #[Test]
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->method('getIdentifier')->willReturn(('SomeCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        self::assertSame('vfs://Foo/Cache/Data/SomeCache/', $backend->getCacheDirectory());
    }

    #[Test]
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches(): void
    {
        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Foo/Cache');

        $backend = $this->prepareDefaultBackend();
        $frontend = new PhpFrontend('SomeCache', $backend);
        $backend->setCache($frontend);

        self::assertSame('vfs://Foo/Cache/Code/SomeCache/', $backend->getCacheDirectory());
    }

    #[Test]
    public function setReallySavesToTheSpecifiedDirectory(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);

        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, strlen($data));
        self::assertSame($data, $retrievedData);
    }

    #[Test]
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

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
        self::assertSame($data2, $retrievedData);
    }

    #[Test]
    public function setAlsoSavesSpecifiedTags(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, ['Tag1', 'Tag2']);

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, strlen($data), 9);
        self::assertSame('Tag1 Tag2', $retrievedData);
    }

    #[Test]
    public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength(): void
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
            ->onlyMethods(['writeCacheFile'])
            ->disableOriginalConstructor()
            ->getMock();

        $backend->expects($this->once())->method('writeCacheFile')->willReturn(false);
        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCacheDirectory($cachePath);

        $backend->set($entryIdentifier, 'cache data');
    }

    #[Test]
    public function setCacheDetectsAndLoadsAFrozenCache(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMockBuilder(FileBackend::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCache($mockCache);
        $backend->set($entryIdentifier, $data);
        $backend->freeze();
        unset($backend);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);
        $backend->setCache($mockCache);

        self::assertTrue($backend->isFrozen());
        self::assertEquals($data, $backend->get($entryIdentifier));
    }

    #[Test]
    public function getReturnsContentOfTheCorrectCacheFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->onlyMethods(['freeze'])
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

    #[Test]
    public function getReturnsFalseForExpiredEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend([], [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        $entryIdentifier = 'ExpiredEntry';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        $entryDto = new FileBackendEntryDto('data', [], time() - 5);
        $backend->setCache($mockCache);
        file_put_contents($pathAndFilename, (string)$entryDto);

        self::assertFalse($backend->get($entryIdentifier));
    }

    #[Test]
    public function getDoesUseInternalGetIfTheCacheIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));
        /** @var MockObject $backend */
        $backend = $this->prepareDefaultBackend(['internalGet'], [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);

        // used in freeze()
        $backend->expects($this->once())->method('internalGet')->willReturn('some data');
        $backend->setCache($mockCache);
        $backend->set('foo', 'some data');
        $backend->freeze();
        self::assertEquals('some data', $backend->get('foo'));
        self::assertFalse($backend->get('bar'));
    }

    #[Test]
    public function hasReturnsTrueIfAnEntryExists(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data);

        self::assertTrue($backend->has($entryIdentifier), 'has() did not return true.');
        self::assertFalse($backend->has($entryIdentifier . 'Not'), 'has() did not return false.');
    }

    #[Test]
    public function hasReturnsFalseForExpiredEntries(): void
    {
        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->willReturnOnConsecutiveCalls(true, false);

        self::assertFalse($backend->has('foo'));
        self::assertTrue($backend->has('bar'));
    }

    #[Test]
    public function hasDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired'], [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ]);
        $backend->setCache($mockCache);

        $backend->expects($this->never())->method('isCacheFileExpired');

        $backend->set('foo', 'some data');
        $backend->freeze();
        self::assertTrue($backend->has('foo'));
        self::assertFalse($backend->has('bar'));
    }

    #[Test]
    public function removeReallyRemovesACacheEntry(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

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
    public static function invalidEntryIdentifiers(): \Iterator
    {
        yield 'trailing slash' => ['/myIdentifer'];
        yield 'trailing dot and slash' => ['./myIdentifer'];
        yield 'trailing two dots and slash' => ['../myIdentifier'];
        yield 'trailing with multiple dots and slashes' => ['.././../myIdentifier'];
        yield 'slash in middle part' => ['my/Identifier'];
        yield 'dot and slash in middle part' => ['my./Identifier'];
        yield 'two dots and slash in middle part' => ['my../Identifier'];
        yield 'multiple dots and slashes in middle part' => ['my.././../Identifier'];
        yield 'pending slash' => ['myIdentifier/'];
        yield 'pending dot and slash' => ['myIdentifier./'];
        yield 'pending dots and slash' => ['myIdentifier../'];
        yield 'pending multiple dots and slashes' => ['myIdentifier.././../'];
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function setThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));
        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->set($identifier, 'cache data', []);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function getThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->setCache($mockCache);

        $backend->get($identifier);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function hasThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $backend = $this->prepareDefaultBackend([]);

        $backend->has($identifier);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function removeThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->remove($identifier);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function requireOnceThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $backend->requireOnce($identifier);
    }

    #[Test]
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntry';

        $data = '<?php return "foo"; ?>';
        $backend->set($entryIdentifier, $data);

        $loadedData = $backend->requireOnce($entryIdentifier);
        self::assertEquals('foo', $loadedData);
    }

    #[Test]
    public function requireOnceDoesNotCheckExpiryTimeIfBackendIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->setCache($mockCache);

        $backend->expects($this->never())->method('isCacheFileExpired');

        $data = '<?php return "foo"; ?>';
        $backend->set('FooEntry', $data);

        $backend->freeze();

        $loadedData = $backend->requireOnce('FooEntry');
        self::assertEquals('foo', $loadedData);
    }

    #[Test]
    public function requireOnceDoesNotSwallowExceptionsOfTheIncludedFile(): void
    {
        $this->expectException(\Exception::class);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithException';
        $backend->set($entryIdentifier, '<?php throw new \Exception(); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    #[Test]
    public function requireOnceDoesNotSwallowPhpWarningsOfTheIncludedFile(): void
    {
        set_error_handler(
            static function ($errno, $errstr) {
                restore_error_handler();
                throw new \ErrorException($errstr, $errno);
            },
            E_USER_WARNING
        );

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Warning!');

        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithPhpWarning';
        $backend->set($entryIdentifier, '<?php trigger_error("Warning!", E_USER_WARNING); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    #[Test]
    public function requireOnceDoesNotSwallowPhpNoticesOfTheIncludedFile(): void
    {
        set_error_handler(
            static function ($errno, $errstr) {
                restore_error_handler();
                throw new \ErrorException($errstr, $errno);
            },
            E_USER_NOTICE
        );

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Notice!');

        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntryWithPhpNotice';
        $backend->set($entryIdentifier, '<?php trigger_error("Notice!", E_USER_NOTICE); ?>');
        $backend->requireOnce($entryIdentifier);
    }

    #[Test]
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $data = 'some data' . microtime();
        $backend->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendFileTest3', $data, ['UnitTestTag%test']);

        $expectedEntry = 'BackendFileTest2';

        $actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
        self::assertIsArray($actualEntries);

        self::assertSame($expectedEntry, array_pop($actualEntries));
    }

    #[Test]
    public function findIdentifiersByTagReturnsEmptyArrayForExpiredEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special'], -100);
        $backend->set('BackendFileTest3', $data, ['UnitTestTag%test']);

        self::assertSame([], $backend->findIdentifiersByTag('UnitTestTag%special'));
        self::assertSame(['BackendFileTest1', 'BackendFileTest3'], $backend->findIdentifiersByTag('UnitTestTag%test'));
    }

    #[Test]
    public function flushRemovesAllCacheEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

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

    #[Test]
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag(): void
    {
        $backend = $this->prepareDefaultBackend(['findIdentifiersByTags', 'remove']);

        $backend->expects($this->once())->method('findIdentifiersByTags')->with(['UnitTestTag%special'])->willReturn((['foo', 'bar', 'baz']));
        $matcher = self::atLeast(3);
        $backend->expects($matcher)->method('remove')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('foo', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('bar', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('baz', $parameters[0]);
            }

            return true;
        });

        $backend->flushByTag('UnitTestTag%special');
    }

    #[Test]
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags(): void
    {
        /** @var MockObject $backend */
        $backend = $this->prepareDefaultBackend(['findIdentifiersByTags', 'remove']);
        $backend->expects(self::once())->method('findIdentifiersByTags')->with(['UnitTestTag%special'])->willReturn(['foo', 'bar', 'baz']);
        $matcher = self::atLeast(3);
        $backend->expects($matcher)->method('remove')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('foo', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('bar', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('baz', $parameters[0]);
            }

            return true;
        });

        $backend->flushByTags(['UnitTestTag%special']);

    }

    #[Test]
    public function collectGarbageRemovesExpiredCacheEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend(['isCacheFileExpired']);
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->willReturnOnConsecutiveCalls(true, false);
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

    #[Test]
    public function flushUnfreezesTheCache(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('UnitTestCache'));

        $backend = $this->prepareDefaultBackend();
        $backend->setCache($mockCache);
        $backend->freeze();

        self::assertTrue($backend->isFrozen());
        $backend->flush();
        self::assertFalse($backend->isFrozen());
    }

    #[Test]
    public function backendAllowsForIteratingOverEntries(): void
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
            self::assertSame(sprintf('entry-%s', $i), $entryIdentifier);
            self::assertEquals('some data ' . $i, $data);
            $i++;
        }
        self::assertSame(100, $i);
    }

    /**
     * @param array $backendMockMethods
     * @param array $environmentConfiguration
     * @return FileBackend|MockObject
     */
    protected function prepareDefaultBackend($backendMockMethods = [], array $environmentConfiguration = ['~Testing', 'vfs://Foo/', 255]): FileBackend
    {
        if ($environmentConfiguration[0][0] === '~') {
            $environmentConfiguration[0] = __DIR__ . $environmentConfiguration[0];
        }
        $mockEnvironmentConfiguration = $this->createEnvironmentConfigurationMock($environmentConfiguration);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->onlyMethods($backendMockMethods)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);

        return $backend;
    }

    /**
     * @param array $constructorArguments
     * @return EnvironmentConfiguration|MockObject
     */
    protected function createEnvironmentConfigurationMock(array $constructorArguments)
    {
        return $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs($constructorArguments)->onlyMethods([])->getMock();
    }
}
