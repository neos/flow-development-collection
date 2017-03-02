<?php
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

use org\bovigo\vfs\vfsStream;
use Neos\Utility\Files;

/**
 * Testcase for the Utility Files class
 */
class FilesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    public function setUp()
    {
        vfsStream::setup('Foo');

        $intendedTemporaryDirectory = sys_get_temp_dir() . '/' . str_replace('\\', '_', __CLASS__);
        if (!file_exists($intendedTemporaryDirectory)) {
            mkdir($intendedTemporaryDirectory);
        }
        $this->temporaryDirectory = realpath($intendedTemporaryDirectory);
    }

    public function tearDown()
    {
        Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @test
     */
    public function getUnixStylePathWorksForPathWithoutSlashes()
    {
        $path = 'foobar';
        $this->assertEquals('foobar', Files::getUnixStylePath($path));
    }

    /**
     * @test
     */
    public function getUnixStylePathWorksForPathWithForwardSlashes()
    {
        $path = 'foo/bar/test/';
        $this->assertEquals('foo/bar/test/', Files::getUnixStylePath($path));
    }

    /**
     * @test
     */
    public function getUnixStylePathWorksForPathWithBackwardSlashes()
    {
        $path = 'foo\\bar\\test\\';
        $this->assertEquals('foo/bar/test/', Files::getUnixStylePath($path));
    }

    /**
     * @test
     */
    public function getUnixStylePathWorksForPathWithForwardAndBackwardSlashes()
    {
        $path = 'foo/bar\\test/';
        $this->assertEquals('foo/bar/test/', Files::getUnixStylePath($path));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForEmptyPath()
    {
        $this->assertEquals('', Files::concatenatePaths([]));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForOnePath()
    {
        $this->assertEquals('foo', Files::concatenatePaths(['foo']));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForTwoPath()
    {
        $this->assertEquals('foo/bar', Files::concatenatePaths(['foo', 'bar']));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForPathsWithLeadingSlash()
    {
        $this->assertEquals('/foo/bar', Files::concatenatePaths(['/foo', 'bar']));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForPathsWithTrailingSlash()
    {
        $this->assertEquals('foo/bar', Files::concatenatePaths(['foo', 'bar/']));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForPathsWithLeadingAndTrailingSlash()
    {
        $this->assertEquals('/foo/bar/bar/foo', Files::concatenatePaths(['/foo/bar/', '/bar/foo/']));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForBrokenPaths()
    {
        $this->assertEquals('/foo/bar/bar', Files::concatenatePaths(['\\foo/bar\\', '\\bar']));
    }

    /**
     * @test
     */
    public function concatenatePathsWorksForEmptyPathArrayElements()
    {
        $this->assertEquals('foo/bar', Files::concatenatePaths(['foo', '', 'bar']));
    }

    /**
     * @test
     */
    public function getUnixStylePathWorksForPathWithDriveLetterAndBackwardSlashes()
    {
        $path = 'c:\\foo\\bar\\test\\';
        $this->assertEquals('c:/foo/bar/test/', Files::getUnixStylePath($path));
    }

    /**
     */
    public function pathsWithProtocol()
    {
        return [
            ['file:///foo\\bar', 'file:///foo/bar'],
            ['vfs:///foo\\bar', 'vfs:///foo/bar'],
            ['phar:///foo\\bar', 'phar:///foo/bar']
        ];
    }

    /**
     * @test
     * @param string $path
     * @param string $expected
     * @dataProvider pathsWithProtocol
     */
    public function getUnixStylePathWorksForPathWithProtocol($path, $expected)
    {
        $this->assertEquals($expected, Files::getUnixStylePath($path));
    }

    /**
     * @test
     */
    public function is_linkReturnsFalseForNonExistingFiles()
    {
        $this->assertFalse(Files::is_link('NonExistingPath'));
    }

    /**
     * @test
     */
    public function is_linkReturnsFalseForExistingFileThatIsNoSymlink()
    {
        $targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
        file_put_contents($targetPathAndFilename, 'some data');
        $this->assertFalse(Files::is_link($targetPathAndFilename));
    }

    /**
     * @test
     */
    public function is_linkReturnsTrueForExistingSymlink()
    {
        $targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
        file_put_contents($targetPathAndFilename, 'some data');
        $linkPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestLink');
        if (file_exists($linkPathAndFilename)) {
            @unlink($linkPathAndFilename);
        }
        symlink($targetPathAndFilename, $linkPathAndFilename);
        $this->assertTrue(Files::is_link($linkPathAndFilename));
    }

    /**
     * @test
     */
    public function is_linkReturnsFalseForExistingDirectoryThatIsNoSymlink()
    {
        $targetPath = Files::concatenatePaths([dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory']) . '/';
        if (!is_dir($targetPath)) {
            Files::createDirectoryRecursively($targetPath);
        }
        $this->assertFalse(Files::is_link($targetPath));
    }

    /**
     * @test
     */
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
        symlink($targetPath, $linkPath);
        $this->assertTrue(Files::is_link($linkPath));
    }

    /**
     * @test
     */
    public function is_linkReturnsFalseForStreamWrapperPaths()
    {
        $targetPath = 'vfs://Foo/Bar';
        if (!is_dir($targetPath)) {
            Files::createDirectoryRecursively($targetPath);
        }
        $this->assertFalse(Files::is_link($targetPath));
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\FilesException
     */
    public function emptyDirectoryRecursivelyThrowsExceptionIfSpecifiedPathDoesNotExist()
    {
        Files::emptyDirectoryRecursively('NonExistingPath');
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\FilesException
     */
    public function removeDirectoryRecursivelyThrowsExceptionIfSpecifiedPathDoesNotExist()
    {
        Files::removeDirectoryRecursively('NonExistingPath');
    }

    /**
     * @test
     */
    public function removeEmptyDirectoriesOnPathRemovesAllDirectoriesOnPathIfTheyAreEmpty()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        $this->assertFalse(file_exists('vfs://Foo'));
    }

    /**
     * @test
     */
    public function removeEmptyDirectoriesOnPathRemovesOnlyDirectoriesWhichAreEmpty()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        file_put_contents('vfs://Foo/Bar/someFile.txt', 'x');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        $this->assertTrue(file_exists('vfs://Foo/Bar/someFile.txt'));
        $this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
    }

    /**
     * @test
     */
    public function removeEmptyDirectoriesOnPathDoesNotRemoveAnythingIfTopLevelPathContainsFile()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        file_put_contents('vfs://Foo/Bar/Baz/Quux/someFile.txt', 'x');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        $this->assertTrue(file_exists('vfs://Foo/Bar/Baz/Quux/someFile.txt'));
    }

    /**
     * @test
     */
    public function removeEmptyDirectoriesOnPathAlsoRemovesOSXFinderFilesIfNecessary()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        file_put_contents('vfs://Foo/Bar/someFile.txt', 'x');
        file_put_contents('vfs://Foo/Bar/Baz/.DS_Store', 'x');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
        $this->assertTrue(file_exists('vfs://Foo/Bar/someFile.txt'));
        $this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
    }

    /**
     * @test
     */
    public function removeEmptyDirectoriesOnPathRemovesOnlyDirectoriesBelowTheGivenBasePath()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Foo/Bar');
        $this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
        $this->assertTrue(file_exists('vfs://Foo/Bar'));

        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Foo/Bar/');
        $this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
        $this->assertTrue(file_exists('vfs://Foo/Bar'));
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\FilesException
     */
    public function removeEmptyDirectoriesOnPathThrowsExceptionIfBasePathIsNotParentOfPath()
    {
        Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
        Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Other/Bar');
    }

    /**
     * @test
     */
    public function unlinkProperlyRemovesSymlinksPointingToFiles()
    {
        $targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
        file_put_contents($targetPathAndFilename, 'some data');
        $linkPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestLink');
        if (file_exists($linkPathAndFilename)) {
            @unlink($linkPathAndFilename);
        }
        symlink($targetPathAndFilename, $linkPathAndFilename);
        $this->assertTrue(Files::unlink($linkPathAndFilename));
        $this->assertTrue(file_exists($targetPathAndFilename));
        $this->assertFalse(file_exists($linkPathAndFilename));
    }

    /**
     * @test
     */
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
        symlink($targetPath, $linkPath);
        $this->assertTrue(Files::unlink($linkPath));
        $this->assertTrue(file_exists($targetPath));
        $this->assertFalse(file_exists($linkPath));
    }

    /**
     * @test
     * @outputBuffering enabled
     *     ... because the chmod call in ResourceManager emits a warning making this fail in strict mode
     */
    public function unlinkReturnsTrueIfSpecifiedPathDoesNotExist()
    {
        $this->assertTrue(Files::unlink('NonExistingPath'));
    }

    /**
     * @test
     */
    public function copyDirectoryRecursivelyCreatesTargetAsExpected()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target');

        $this->assertTrue(is_dir('vfs://Foo/target/bar/baz'));
        $this->assertTrue(is_file('vfs://Foo/target/bar/baz/file.txt'));
        $this->assertEquals('source content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
    }

    /**
     * @test
     */
    public function copyDirectoryRecursivelyCopiesDotFilesIfRequested()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/.file.txt', 'source content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target', false, true);

        $this->assertTrue(is_dir('vfs://Foo/target/bar/baz'));
        $this->assertTrue(is_file('vfs://Foo/target/bar/baz/.file.txt'));
        $this->assertEquals('source content', file_get_contents('vfs://Foo/target/bar/baz/.file.txt'));
    }

    /**
     * @test
     */
    public function copyDirectoryRecursivelyOverwritesTargetFiles()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

        Files::createDirectoryRecursively('vfs://Foo/target/bar/baz');
        file_put_contents('vfs://Foo/target/bar/baz/file.txt', 'target content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target');
        $this->assertEquals('source content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
    }

    /**
     * @test
     */
    public function copyDirectoryRecursivelyKeepsExistingTargetFilesIfRequested()
    {
        Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
        file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

        Files::createDirectoryRecursively('vfs://Foo/target/bar/baz');
        file_put_contents('vfs://Foo/target/bar/baz/file.txt', 'target content');

        Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target', true);
        $this->assertEquals('target content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
    }

    /**
     * @return array
     */
    public function bytesToSizeStringDataProvider()
    {
        return [

            // invalid values
            [
                'bytes' => 'invalid',
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0 B'
            ],
            [
                'bytes' => '-100',
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0.00 B'
            ],
            [
                'bytes' => -100,
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0.00 B'
            ],
            [
                'bytes' => '',
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0.00 B'
            ],
            [
                'bytes' => [],
                'decimals' => 2,
                'decimalSeparator' => ',',
                'thousandsSeparator' => null,
                'expected' => '0,00 B'
            ],

            // valid values
            [
                'bytes' => 123,
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '123 B'
            ],
            [
                'bytes' => '43008',
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '42.0 KB'
            ],
            [
                'bytes' => 1024,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 KB'
            ],
            [
                'bytes' => 1023,
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1,023.00 B'
            ],
            [
                'bytes' => 1073741823,
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1,024 MB'
            ],
            [
                'bytes' => 1073741823,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => '.',
                'expected' => '1.024.0 MB'
            ],
            [
                'bytes' => pow(1024, 5),
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 PB'
            ],
            [
                'bytes' => pow(1024, 8),
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 YB'
            ]
        ];
    }

    /**
     * @param $bytes
     * @param $decimals
     * @param $decimalSeparator
     * @param $thousandsSeparator
     * @param $expected
     * @test
     * @dataProvider bytesToSizeStringDataProvider
     */
    public function bytesToSizeStringTests($bytes, $decimals, $decimalSeparator, $thousandsSeparator, $expected)
    {
        $actualResult = Files::bytesToSizeString($bytes, $decimals, $decimalSeparator, $thousandsSeparator);
        $this->assertSame($expected, $actualResult);
    }

    /**
     * @return array
     */
    public function sizeStringToBytesDataProvider()
    {
        return [

            // invalid values
            [
                'sizeString' => 'invalid',
                'expected' => 0.0
            ],
            [
                'sizeString' => '',
                'expected' => 0.0
            ],
            [
                'sizeString' => false,
                'expected' => 0.0
            ],

            // valid values
            [
                'sizeString' => '12345',
                'expected' => 12345.0
            ],
            [
                'sizeString' => '54321 b',
                'expected' => 54321.0
            ],
            [
                'sizeString' => '1024M',
                'expected' => 1073741824.0
            ],
            [
                'sizeString' => '1024.0 MB',
                'expected' => 1073741824.0
            ],
            [
                'sizeString' => '500 MB',
                'expected' => 524288000.0
            ],
            [
                'sizeString' => '500m',
                'expected' => 524288000.0
            ],
            [
                'sizeString' => '1.0 KB',
                'expected' => 1024.0
            ],
            [
                'sizeString' => '1 GB',
                'expected' => (float)pow(1024, 3)
            ],
            [
                'sizeString' => '1 Z',
                'expected' => (float)pow(1024, 7)
            ]
        ];
    }

    /**
     * @param string $sizeString
     * @param float $expected
     * @test
     * @dataProvider sizeStringToBytesDataProvider
     */
    public function sizeStringToBytesTests($sizeString, $expected)
    {
        $actualResult = Files::sizeStringToBytes($sizeString);
        $this->assertSame($expected, $actualResult);
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\FilesException
     */
    public function sizeStringThrowsExceptionIfTheSpecifiedUnitIsUnknown()
    {
        Files::sizeStringToBytes('123 UnknownUnit');
    }
}
