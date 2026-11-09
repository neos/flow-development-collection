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

use Neos\Cache\Backend\SimpleFileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Tests\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for the SimpleFileBackend
 */
final class SimpleFileBackendTest extends BaseTestCase
{
    /**
     * @var FrontendInterface|MockObject
     */
    protected $mockCacheFrontend;

    /**
     * @var EnvironmentConfiguration|MockObject
     */
    protected $mockEnvironmentConfiguration;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        vfsStream::setup('Temporary/Directory/');

        $this->mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                __DIR__ . '~Testing',
                'vfs://Temporary/Directory/',
                1024
            ])->getMock();

        $this->mockCacheFrontend = $this->createMock(FrontendInterface::class);
    }

    /**
     * Convenience function to retrieve an instance of SimpleFileBackend with required dependencies
     *
     * @param array $options
     * @param FrontendInterface $mockCacheFrontend
     * @return SimpleFileBackend
     */
    protected function getSimpleFileBackend(array $options = [], ?FrontendInterface $mockCacheFrontend = null): SimpleFileBackend
    {
        $simpleFileBackend = new SimpleFileBackend($this->mockEnvironmentConfiguration, $options);

        if ($mockCacheFrontend === null) {
            $simpleFileBackend->setCache($this->mockCacheFrontend);
        } else {
            $simpleFileBackend->setCache($mockCacheFrontend);
        }

        return $simpleFileBackend;
    }

    #[Test]
    public function setCacheThrowsExceptionOnNonWritableDirectory(): void
    {
        $this->expectException(Exception::class);
        $mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                __DIR__ . '~Testing',
                'vfs://Some/NonExisting/Directory/',
                1024
            ])
            ->getMock();
        $simpleFileBackend = new SimpleFileBackend($mockEnvironmentConfiguration, []);

        $simpleFileBackend->setCache($this->mockCacheFrontend);
        $this->getSimpleFileBackend();
    }

    #[Test]
    public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1248710426);
        $mockEnvironmentConfiguration = new EnvironmentConfiguration(
            __DIR__ . '~Testing',
            'vfs://Temporary/Directory/',
            5
        );

        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMockBuilder(SimpleFileBackend::class)->onlyMethods(['writeCacheFile'])->disableOriginalConstructor()->getMock();
        $backend->expects($this->once())->method('writeCacheFile')->willReturn(false);
        $this->inject($backend, 'environmentConfiguration', $mockEnvironmentConfiguration);

        $backend->set($entryIdentifier, 'cache data');
    }

    #[Test]
    public function setCacheDirectoryAllowsToSetTheCurrentCacheDirectory(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('SomeCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');
        mkdir('vfs://Temporary/Directory/OtherDirectory');

        $simpleFileBackend = $this->getSimpleFileBackend(['cacheDirectory' => 'vfs://Temporary/Directory/OtherDirectory']);
        self::assertSame('vfs://Temporary/Directory/OtherDirectory/', $simpleFileBackend->getCacheDirectory());
    }

    #[Test]
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('SomeCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');

        $simpleFileBackend = $this->getSimpleFileBackend();
        self::assertSame('vfs://Temporary/Directory/Cache/Data/SomeCache/', $simpleFileBackend->getCacheDirectory());
    }

    #[Test]
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches(): void
    {
        /** @var PhpFrontend|MockObject $mockPhpCacheFrontend */
        $mockPhpCacheFrontend = $this->createMock(PhpFrontend::class);
        $mockPhpCacheFrontend->method('getIdentifier')->willReturn(('SomePhpCache'));

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');

        $simpleFileBackend = $this->getSimpleFileBackend([], $mockPhpCacheFrontend);
        self::assertSame('vfs://Temporary/Directory/Cache/Code/SomePhpCache/', $simpleFileBackend->getCacheDirectory());
    }

    #[Test]
    public function setReallySavesToTheSpecifiedDirectory(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data = uniqid('some data', true);
        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data);

        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename);
        self::assertSame($data, $retrievedData);
    }

    #[Test]
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data1 = uniqid('some data', true);
        $data2 = uniqid('some other data', true);
        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data1);
        $simpleFileBackend->set($entryIdentifier, $data2);

        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename);
        self::assertSame($data2, $retrievedData);
    }

    #[Test]
    public function setDoesNotOverwriteIfLockNotAcquired(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data1 = uniqid('some data', true);
        $data2 = uniqid('some other data', true);
        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data1);

        $file = fopen($pathAndFilename, 'rb');

        flock($file, LOCK_EX);
        try {
            $simpleFileBackend->set($entryIdentifier, $data2);
        } catch (Exception $e) {
        }
        flock($file, LOCK_UN);
        fclose($file);

        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename);
        self::assertSame($data1, $retrievedData);
    }

    #[Test]
    public function getReturnsContentOfTheCorrectCacheFile(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data1 = uniqid('some data', true);
        $data2 = uniqid('some other data', true);
        $entryIdentifier = 'SimpleFileBackendTest';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data1);
        $simpleFileBackend->set($entryIdentifier, $data2);

        self::assertSame($data2, $simpleFileBackend->get($entryIdentifier));
    }

    #[Test]
    public function getSupportsEmptyData(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $data = '';
        $entryIdentifier = 'SimpleFileBackendTest';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, $data);

        self::assertSame($data, $simpleFileBackend->get($entryIdentifier));
    }

    #[Test]
    public function getReturnsFalseForDeletedFiles(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, 'some data');

        unlink($pathAndFilename);

        self::assertFalse($simpleFileBackend->get($entryIdentifier));
    }

    #[Test]
    public function hasReturnsTrueIfAnEntryExists(): void
    {
        $entryIdentifier = 'SimpleFileBackendTest';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, 'some data');

        self::assertTrue($simpleFileBackend->has($entryIdentifier));
    }

    #[Test]
    public function hasReturnsFalseIfAnEntryDoesNotExist(): void
    {
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set('SomeEntryIdentifier', 'some data');

        self::assertFalse($simpleFileBackend->has('SomeNonExistingEntryIdentifier'));
    }

    #[Test]
    public function removeReallyRemovesACacheEntry(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $entryIdentifier = 'SimpleFileBackendTest';
        $pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, 'some data');

        self::assertFileExists($pathAndFilename);
        self::assertTrue($simpleFileBackend->has($entryIdentifier));

        $simpleFileBackend->remove($entryIdentifier);

        self::assertFileDoesNotExist($pathAndFilename);
        self::assertFalse($simpleFileBackend->has($entryIdentifier));
    }

    /**
     * @return \Iterator<(int | string), mixed>
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

    /**
     * @param string $identifier
     */
    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function setThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($identifier, 'some data');
    }

    /**
     * @param string $identifier
     */
    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function getThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->get($identifier);
    }

    /**
     * @param string $identifier
     */
    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function hasThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->has($identifier);
    }

    /**
     * @param string $identifier
     */
    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function removeThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->remove($identifier);
    }

    /**
     * @param string $identifier
     */
    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function requireOnceThrowsExceptionForInvalidIdentifier($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->requireOnce($identifier);
    }

    #[Test]
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile(): void
    {
        $entryIdentifier = 'SomeValidPhpEntry';

        $simpleFileBackend = $this->getSimpleFileBackend();

        $data = '<?php return "foo";';
        $simpleFileBackend->set($entryIdentifier, $data);

        $loadedData = $simpleFileBackend->requireOnce($entryIdentifier);
        self::assertEquals('foo', $loadedData);
    }

    #[Test]
    public function requireOnceDoesNotSwallowExceptionsOfTheIncludedFile(): void
    {
        $this->expectException(\Exception::class);
        $entryIdentifier = 'SomePhpEntryWithException';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, '<?php throw new \Exception(); ?>');
        $simpleFileBackend->requireOnce($entryIdentifier);
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

        $entryIdentifier = 'SomePhpEntryWithPhpWarning';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, '<?php trigger_error("Warning!", E_USER_WARNING); ?>');
        $simpleFileBackend->requireOnce($entryIdentifier);
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

        $entryIdentifier = 'SomePhpEntryWithPhpNotice';

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier, '<?php trigger_error("Notice!", E_USER_NOTICE); ?>');
        $simpleFileBackend->requireOnce($entryIdentifier);
    }

    #[Test]
    public function flushRemovesAllCacheEntries(): void
    {
        $this->mockCacheFrontend->method('getIdentifier')->willReturn(('UnitTestCache'));

        $entryIdentifier1 = 'SimpleFileBackendTest1';
        $pathAndFilename1 = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier1;
        $entryIdentifier2 = 'SimpleFileBackendTest2';
        $pathAndFilename2 = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier2;

        $simpleFileBackend = $this->getSimpleFileBackend();
        $simpleFileBackend->set($entryIdentifier1, 'some data');
        $simpleFileBackend->set($entryIdentifier2, 'some more data');

        self::assertFileExists($pathAndFilename1);
        self::assertFileExists($pathAndFilename2);
        self::assertTrue($simpleFileBackend->has($entryIdentifier1));
        self::assertTrue($simpleFileBackend->has($entryIdentifier2));

        $simpleFileBackend->flush();

        self::assertFileDoesNotExist($pathAndFilename1);
        self::assertFalse($simpleFileBackend->has($entryIdentifier1));
        self::assertFileDoesNotExist($pathAndFilename2);
        self::assertFalse($simpleFileBackend->has($entryIdentifier2));
    }

    #[Test]
    public function backendAllowsForIteratingOverEntries(): void
    {
        $simpleFileBackend = $this->getSimpleFileBackend();

        for ($i = 0; $i < 100; $i++) {
            $entryIdentifier = sprintf('entry-%s', $i);
            $data = 'some data ' . $i;
            $simpleFileBackend->set($entryIdentifier, $data);
        }

        $entries = [];
        foreach ($simpleFileBackend as $entryIdentifier => $data) {
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

    #[Test]
    public function iterationOverEmptyCacheYieldsNoData(): void
    {
        $backend = $this->getSimpleFileBackend();
        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }

    #[Test]
    public function iterationOverNotEmptyCacheYieldsData(): void
    {
        $backend = $this->getSimpleFileBackend();

        $backend->set('first', 'firstData');
        $backend->set('second', 'secondData');

        $data = \iterator_to_array($backend);
        // the backend does not guarantee any particular iteration order
        ksort($data);
        self::assertSame(
            ['first' => 'firstData', 'second' => 'secondData'],
            $data
        );
    }

    #[Test]
    public function iterationResetsWhenDataIsSet(): void
    {
        $backend = $this->getSimpleFileBackend();

        $backend->set('first', 'firstData');
        $backend->set('second', 'secondData');
        \iterator_to_array($backend);

        $backend->set('third', 'thirdData');

        $data = \iterator_to_array($backend);
        // the backend does not guarantee any particular iteration order
        ksort($data);
        self::assertSame(
            ['first' => 'firstData', 'second' => 'secondData', 'third' => 'thirdData'],
            $data
        );
    }

    #[Test]
    public function iterationResetsWhenDataGetsRemoved(): void
    {
        $backend = $this->getSimpleFileBackend();

        $backend->set('first', 'firstData');
        \iterator_to_array($backend);

        $backend->remove('first');

        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }

    #[Test]
    public function iterationResetsWhenDataFlushed(): void
    {
        $backend = $this->getSimpleFileBackend();

        $backend->set('first', 'firstData');
        \iterator_to_array($backend);

        $backend->flush();

        $data = \iterator_to_array($backend);
        self::assertEmpty($data);
    }
}
