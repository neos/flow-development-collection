<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Utility;

/*
 * This file is part of the Neos.Utility.Files package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the Utility Files class
 */
final class FilesTest extends TestCase
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $intendedTemporaryDirectory = sys_get_temp_dir() . '/' . str_replace('\\', '_', __CLASS__);
        if (!file_exists($intendedTemporaryDirectory)) {
            mkdir($intendedTemporaryDirectory);
        }
        $this->temporaryDirectory = realpath($intendedTemporaryDirectory);
    }

    protected function tearDown(): void
    {
        Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @param string $target
     * @param string $link
     * @return boolean
     * @throws \Exception
     */
    protected function trySymlink($target, $link)
    {
        try {
            return symlink($target, $link);
        } catch (\Exception $e) {
            if (DIRECTORY_SEPARATOR !== '/') {
                $this->markTestSkipped('Your Windows Installation does not allow the PHP process to create symlinks. Try running tests from an admin elevated command line.');
                return false;
            }
            throw $e;
        }
    }

    #[Test]
    public function getUnixStylePathWorksForPathWithoutSlashes()
    {
        $path = 'foobar';
        self::assertSame('foobar', Files::getUnixStylePath($path));
    }

    #[Test]
    public function getUnixStylePathWorksForPathWithForwardSlashes()
    {
        $path = 'foo/bar/test/';
        self::assertSame('foo/bar/test/', Files::getUnixStylePath($path));
    }

    #[Test]
    public function getUnixStylePathWorksForPathWithBackwardSlashes()
    {
        $path = 'foo\\bar\\test\\';
        self::assertSame('foo/bar/test/', Files::getUnixStylePath($path));
    }

    #[Test]
    public function getUnixStylePathWorksForPathWithForwardAndBackwardSlashes()
    {
        $path = 'foo/bar\\test/';
        self::assertSame('foo/bar/test/', Files::getUnixStylePath($path));
    }

    #[Test]
    public function concatenatePathsWorksForEmptyPath()
    {
        self::assertSame('', Files::concatenatePaths([]));
    }

    #[Test]
    public function concatenatePathsWorksForOnePath()
    {
        self::assertSame('foo', Files::concatenatePaths(['foo']));
    }

    #[Test]
    public function concatenatePathsWorksForTwoPath()
    {
        self::assertSame('foo/bar', Files::concatenatePaths(['foo', 'bar']));
    }

    #[Test]
    public function concatenatePathsWorksForPathsWithLeadingSlash()
    {
        self::assertSame('/foo/bar', Files::concatenatePaths(['/foo', 'bar']));
    }

    #[Test]
    public function concatenatePathsWorksForPathsWithTrailingSlash()
    {
        self::assertSame('foo/bar', Files::concatenatePaths(['foo', 'bar/']));
    }

    #[Test]
    public function concatenatePathsWorksForPathsWithLeadingAndTrailingSlash()
    {
        self::assertSame('/foo/bar/bar/foo', Files::concatenatePaths(['/foo/bar/', '/bar/foo/']));
    }

    #[Test]
    public function concatenatePathsWorksForBrokenPaths()
    {
        self::assertSame('/foo/bar/bar', Files::concatenatePaths(['\\foo/bar\\', '\\bar']));
    }

    #[Test]
    public function concatenatePathsWorksForEmptyPathArrayElements()
    {
        self::assertSame('foo/bar', Files::concatenatePaths(['foo', '', 'bar']));
    }

    #[Test]
    public function getUnixStylePathWorksForPathWithDriveLetterAndBackwardSlashes()
    {
        $path = 'c:\\foo\\bar\\test\\';
        self::assertSame('c:/foo/bar/test/', Files::getUnixStylePath($path));
    }

    /**
     */
    public static function pathsWithProtocol(): \Iterator
    {
        yield ['file:///foo\\bar', 'file:///foo/bar'];
        yield ['vfs:///foo\\bar', 'vfs:///foo/bar'];
        yield ['phar:///foo\\bar', 'phar:///foo/bar'];
    }

    /**
     * @param string $path
     * @param string $expected
     */
    #[DataProvider('pathsWithProtocol')]
    #[Test]
    public function getUnixStylePathWorksForPathWithProtocol($path, $expected)
    {
        self::assertEquals($expected, Files::getUnixStylePath($path));
    }

    #[Test]
    public function is_linkReturnsFalseForNonExistingFiles()
    {
        self::assertFalse(Files::is_link('NonExistingPath'));
    }

    #[Test]
    public function is_linkReturnsFalseForExistingFileThatIsNoSymlink()
    {
        $targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
        file_put_contents($targetPathAndFilename, 'some data');
        self::assertFalse(Files::is_link($targetPathAndFilename));
    }

    #[Test]
    public function is_linkReturnsTrueForExistingSymlink()
    {
        $targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
        file_put_contents($targetPathAndFilename, 'some data');
        $linkPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestLink');
        if (file_exists($linkPathAndFilename)) {
            @unlink($linkPathAndFilename);
        }
        $this->trySymlink($targetPathAndFilename, $linkPathAndFilename);
        self::assertTrue(Files::is_link($linkPathAndFilename));
    }

    #[Test]
    public function is_linkReturnsFalseForExistingDirectoryThatIsNoSymlink()
    {
        $targetPath = Files::concatenatePaths([dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory']) . '/';
        if (!is_dir($targetPath)) {
            Files::createDirectoryRecursively($targetPath);
        }
        self::assertFalse(Files::is_link($targetPath));
    }

    #[Test]
    public function is_linkReturnsTrueForExistingSymlinkDirectory()
    {
        $targetPath = Files::concatenatePaths([dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory']);
        if (!is_dir($targetPath)) {
            Files::createDirectoryRecursively($targetPath);
        }
        $linkPath = Files::concatenatePaths([dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectoryLink']);
        if (is_dir($linkPath)) {
            Files::removeDirectoryRecursively($linkPath);
        }
        $this->trySymlink($targetPath, $linkPath);
        self::assertTrue(Files::is_link($linkPath));
    }

    #[Test]
    public function is_linkReturnsFalseForStreamWrapperPaths()
    {
        $targetPath = 'vfs://Foo/Bar';
        if (!is_dir($targetPath)) {
            Files::createDirectoryRecursively($targetPath);
        }
        self::assertFalse(Files::is_link($targetPath));
    }

    #[Test]
    public function emptyDirectoryRecursivelyThrowsExceptionIfSpecifiedPathDoesNotExist()
    {
        $this->expectException(FilesException::class);
        Files::emptyDirectoryRecursively('NonExistingPath');
    }

    #[Test]
    public function removeDirectoryRecursivelyThrowsExceptionIfSpecifiedPathDoesNotExist()
    {
        $this->expectException(FilesException::class);
        Files::removeDirectoryRecursively('NonExistingPath');
    }

    #[Test]
    public function removeEmptyDirectoriesOnPathRemovesAllDirectoriesOnPathIfTheyAreEmpty()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        self::assertFileDoesNotExist('vfs://Foo');
    }

    #[Test]
    public function removeEmptyDirectoriesOnPathRemovesOnlyDirectoriesWhichAreEmpty()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        file_put_contents('vfs://Foo/Bar/someFile.txt', 'x');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        self::assertFileExists('vfs://Foo/Bar/someFile.txt');
        self::assertFileDoesNotExist('vfs://Foo/Bar/Baz');
    }

    #[Test]
    public function removeEmptyDirectoriesOnPathDoesNotRemoveAnythingIfTopLevelPathContainsFile()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        file_put_contents('vfs://Foo/Bar/Baz/Quux/someFile.txt', 'x');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        self::assertFileExists('vfs://Foo/Bar/Baz/Quux/someFile.txt');
    }

    #[Test]
    public function removeEmptyDirectoriesOnPathAlsoRemovesOSXFinderFilesIfNecessary()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        file_put_contents('vfs://Foo/Bar/someFile.txt', 'x');
        file_put_contents('vfs://Foo/Bar/Baz/.DS_Store', 'x');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        self::assertFileExists('vfs://Foo/Bar/someFile.txt');
        self::assertFileDoesNotExist('vfs://Foo/Bar/Baz');
    }

    #[Test]
    public function removeEmptyDirectoriesOnPathRemovesOnlyDirectoriesBelowTheGivenBasePath()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Foo/Bar');
        self::assertFileDoesNotExist('vfs://Foo/Bar/Baz');
        self::assertFileExists('vfs://Foo/Bar');

        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Foo/Bar/');
        self::assertFileDoesNotExist('vfs://Foo/Bar/Baz');
        self::assertFileExists('vfs://Foo/Bar');
    }

    #[Test]
    public function removeEmptyDirectoriesOnPathThrowsExceptionIfBasePathIsNotParentOfPath()
    {
        $this->expectException(FilesException::class);
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Other/Bar');
    }

    #[Test]
    public function unlinkProperlyRemovesSymlinksPointingToFiles()
    {
        $targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
        file_put_contents($targetPathAndFilename, 'some data');
        $linkPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestLink');
        if (file_exists($linkPathAndFilename)) {
            @unlink($linkPathAndFilename);
        }
        $this->trySymlink($targetPathAndFilename, $linkPathAndFilename);
        self::assertTrue(Files::unlink($linkPathAndFilename));
        self::assertFileExists($targetPathAndFilename);
        self::assertFileDoesNotExist($linkPathAndFilename);
    }

    #[Test]
    public function unlinkProperlyRemovesSymlinksPointingToDirectories()
    {
        $targetPath = Files::concatenatePaths([dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory']);
        if (!is_dir($targetPath)) {
            Files::createDirectoryRecursively($targetPath);
        }
        $linkPath = Files::concatenatePaths([dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectoryLink']);
        if (is_dir($linkPath)) {
            Files::removeDirectoryRecursively($linkPath);
        }
        $this->trySymlink($targetPath, $linkPath);
        self::assertTrue(Files::unlink($linkPath));
        self::assertFileExists($targetPath);
        self::assertFileDoesNotExist($linkPath);
    }

    /**
     * @outputBuffering enabled
     *     ... because the chmod call in ResourceManager emits a warning making this fail in strict mode
     */
    #[Test]
    public function unlinkReturnsTrueIfSpecifiedPathDoesNotExist()
    {
        self::assertTrue(Files::unlink('NonExistingPath'));
    }

    #[Test]
    public function copyDirectoryRecursivelyCreatesTargetAsExpected()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target');

        self::assertDirectoryExists('vfs://Foo/target/bar/baz');
        self::assertTrue(is_file('vfs://Foo/target/bar/baz/file.txt'));
        self::assertSame('source content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
    }

    #[Test]
    public function copyDirectoryRecursivelyCopiesDotFilesIfRequested()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/.file.txt', 'source content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target', false, true);

        self::assertDirectoryExists('vfs://Foo/target/bar/baz');
        self::assertTrue(is_file('vfs://Foo/target/bar/baz/.file.txt'));
        self::assertSame('source content', file_get_contents('vfs://Foo/target/bar/baz/.file.txt'));
    }

    #[Test]
    public function copyDirectoryRecursivelyOverwritesTargetFiles()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

        Files::createDirectoryRecursively('vfs://Foo/target/bar/baz');
        file_put_contents('vfs://Foo/target/bar/baz/file.txt', 'target content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target');
        self::assertSame('source content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
    }

    #[Test]
    public function copyDirectoryRecursivelyKeepsExistingTargetFilesIfRequested()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

        Files::createDirectoryRecursively('vfs://Foo/target/bar/baz');
        file_put_contents('vfs://Foo/target/bar/baz/file.txt', 'target content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target', true);
        self::assertSame('target content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function bytesToSizeStringDataProvider(): \Iterator
    {
        // invalid values
        yield [
            'bytes' => 'invalid',
            'decimals' => null,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '0 B'
        ];
        yield [
            'bytes' => '-100',
            'decimals' => 2,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '0.00 B'
        ];
        yield [
            'bytes' => -100,
            'decimals' => 2,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '0.00 B'
        ];
        yield [
            'bytes' => '',
            'decimals' => 2,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '0.00 B'
        ];
        yield [
            'bytes' => [],
            'decimals' => 2,
            'decimalSeparator' => ',',
            'thousandsSeparator' => null,
            'expected' => '0,00 B'
        ];
        // valid values
        yield [
            'bytes' => 123,
            'decimals' => null,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '123 B'
        ];
        yield [
            'bytes' => '43008',
            'decimals' => 1,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '42.0 KB'
        ];
        yield [
            'bytes' => 1024,
            'decimals' => 1,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '1.0 KB'
        ];
        yield [
            'bytes' => 1023,
            'decimals' => 2,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '1,023.00 B'
        ];
        yield [
            'bytes' => 1073741823,
            'decimals' => null,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '1,024 MB'
        ];
        yield [
            'bytes' => 1073741823,
            'decimals' => 1,
            'decimalSeparator' => null,
            'thousandsSeparator' => '.',
            'expected' => '1.024.0 MB'
        ];
        yield [
            'bytes' => pow(1024, 5),
            'decimals' => 1,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '1.0 PB'
        ];
        yield [
            'bytes' => pow(1024, 8),
            'decimals' => 1,
            'decimalSeparator' => null,
            'thousandsSeparator' => null,
            'expected' => '1.0 YB'
        ];
    }

    /**
     * @param $bytes
     * @param $decimals
     * @param $decimalSeparator
     * @param $thousandsSeparator
     * @param $expected
     */
    #[DataProvider('bytesToSizeStringDataProvider')]
    #[Test]
    public function bytesToSizeStringTests($bytes, $decimals, $decimalSeparator, $thousandsSeparator, $expected)
    {
        $actualResult = Files::bytesToSizeString($bytes, $decimals, $decimalSeparator, $thousandsSeparator);
        self::assertSame($expected, $actualResult);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function sizeStringToBytesDataProvider(): \Iterator
    {
        // invalid values
        yield [
            'sizeString' => 'invalid',
            'expected' => 0.0
        ];
        yield [
            'sizeString' => '',
            'expected' => 0.0
        ];
        // valid values
        yield [
            'sizeString' => '12345',
            'expected' => 12345.0
        ];
        yield [
            'sizeString' => '54321 b',
            'expected' => 54321.0
        ];
        yield [
            'sizeString' => '1024M',
            'expected' => 1073741824.0
        ];
        yield [
            'sizeString' => '1024.0 MB',
            'expected' => 1073741824.0
        ];
        yield [
            'sizeString' => '500 MB',
            'expected' => 524288000.0
        ];
        yield [
            'sizeString' => '500m',
            'expected' => 524288000.0
        ];
        yield [
            'sizeString' => '1.0 KB',
            'expected' => 1024.0
        ];
        yield [
            'sizeString' => '1 GB',
            'expected' => (float)pow(1024, 3)
        ];
        yield [
            'sizeString' => '1 Z',
            'expected' => (float)pow(1024, 7)
        ];
    }

    /**
     * @param string $sizeString
     * @param float $expected
     */
    #[DataProvider('sizeStringToBytesDataProvider')]
    #[Test]
    public function sizeStringToBytesTests($sizeString, $expected)
    {
        $actualResult = Files::sizeStringToBytes($sizeString);
        self::assertSame($expected, $actualResult);
    }

    #[Test]
    public function sizeStringThrowsExceptionIfTheSpecifiedUnitIsUnknown()
    {
        $this->expectException(FilesException::class);
        Files::sizeStringToBytes('123 UnknownUnit');
    }
}
