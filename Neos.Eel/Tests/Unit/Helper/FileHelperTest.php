<?php
namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\Helper\FileHelper;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Test for FileHelper
 */
class FileHelperTest extends UnitTestCase
{
    protected function setUp(): void
    {
        vfsStream::setup('Foo');
    }

    /**
     * @test
     */
    public function readFileWillReturnFileContents()
    {
        $filepath = 'vfs://Foo/bar.txt';
        $fileContent = 'Hello';
        file_put_contents($filepath, $fileContent);

        $fileHelper = new FileHelper();

        self::assertEquals($fileContent, $fileHelper->readFile($filepath));
    }

    /**
     * @test
     */
    public function getSha1WillReturnTheFileSha1()
    {
        $filepath = 'vfs://Foo/bar.txt';
        $fileContent = 'Hello';
        file_put_contents($filepath, $fileContent);
        $expected = sha1_file($filepath);

        $fileHelper = new FileHelper();

        self::assertEquals($expected, $fileHelper->getSha1($filepath));
    }

    /**
     * @test
     */
    public function getPathInfoReturnsPathInformation()
    {
        $filepath = 'vfs://Foo/bar.txt';
        file_put_contents($filepath, 'does not matter');
        $fileHelper = new FileHelper();

        $result = $fileHelper->fileInfo($filepath);

        self::assertTrue(is_array($result));
        self::assertEquals('txt', $result['extension']);
        self::assertEquals('bar', $result['filename']);
        self::assertEquals('vfs://Foo', $result['dirname']);
        self::assertEquals('bar.txt', $result['basename']);
    }

    /**
     * @test
     */
    public function statReturnsStatInfo()
    {
        $filepath = 'vfs://Foo/bar.txt';
        file_put_contents($filepath, 'does not matter');
        $filesize = filesize($filepath);

        $fileHelper = new FileHelper();

        $result = $fileHelper->stat($filepath);

        self::assertTrue(is_array($result));
        self::assertArrayHasKey('mtime', $result);
        self::assertArrayHasKey('ctime', $result);
        self::assertArrayHasKey('atime', $result);
        self::assertArrayHasKey('size', $result);
        self::assertArrayHasKey('mode', $result);
        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('gid', $result);
        self::assertEquals($filesize, $result['size']);
    }
}
