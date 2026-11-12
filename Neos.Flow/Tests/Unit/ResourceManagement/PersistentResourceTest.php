<?php
namespace Neos\Flow\Tests\Unit\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the PersistentResource class
 */
class PersistentResourceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setFilenameStoresTheFileExtensionInLowerCase()
    {
        $resource = new PersistentResource();
        $resource->setFilename('Something.Jpeg');
        self::assertSame('jpeg', $resource->getFileExtension());
        self::assertSame('Something.jpeg', $resource->getFilename());
    }

    /**
     * @test
     */
    public function setFilenameSetsTheMediaType()
    {
        $resource = new PersistentResource();

        $resource->setFilename('Something.jpg');
        self::assertSame('image/jpeg', $resource->getMediaType());

        $resource->setFilename('Something.png');
        self::assertSame('image/png', $resource->getMediaType());

        $resource->setFilename('Something.Jpeg');
        self::assertSame('image/jpeg', $resource->getMediaType());
    }

    /**
     * @test
     */
    public function setFilenameDoesNotAppendFileExtensionIfItIsEmpty()
    {
        $resource = new PersistentResource();
        $resource->setFilename('FileWithoutExtension');
        self::assertSame('', $resource->getFileExtension());
        self::assertSame('FileWithoutExtension', $resource->getFilename());
    }

    public function filenamesWithTrailingDotsOrWhitespace(): array
    {
        return [
            'trailing dot' => ['foo.php.', 'foo.php', 'php'],
            'trailing dots' => ['foo.php...', 'foo.php', 'php'],
            'trailing space' => ['foo.php ', 'foo.php', 'php'],
            'trailing dots and whitespace' => ["foo.PHP. .\t\n", 'foo.php', 'php'],
            'trailing null byte' => ["foo.php\0", 'foo.php', 'php'],
        ];
    }

    /**
     * @test
     * @dataProvider filenamesWithTrailingDotsOrWhitespace
     */
    public function setFilenameRemovesTrailingDotsAndWhitespace(string $filename, string $expectedFilename, string $expectedExtension)
    {
        $resource = new PersistentResource();
        $resource->setFilename($filename);
        self::assertSame($expectedFilename, $resource->getFilename());
        self::assertSame($expectedExtension, $resource->getFileExtension());
    }

    /**
     * @test
     */
    public function getMediaTypeReturnsMediaTypeBasedOnFileExtension()
    {
        $resource = new PersistentResource();
        $resource->setFilename('file.jpg');
        self::assertSame('image/jpeg', $resource->getMediaType());

        $resource = new PersistentResource();
        $resource->setFilename('file.zip');
        self::assertSame('application/zip', $resource->getMediaType());

        $resource = new PersistentResource();
        $resource->setFilename('file.someunknownextension');
        self::assertSame('application/octet-stream', $resource->getMediaType());
    }

    /**
     * @return array
     */
    public function invalidSha1Values()
    {
        return [
          [''],
          [null],
          ['XYZE2DC421BE4fCD0172E5AFCEEA3970E2f3d940'],
          [new \stdClass()],
          [false],
        ];
    }

    /**
     * @test
     * @dataProvider invalidSha1Values
     */
    public function setSha1RejectsInvalidValues($invalidValue)
    {
        $this->expectException(\InvalidArgumentException::class);
        $resource = new PersistentResource();
        $resource->setSha1($invalidValue);
        self::assertSame('d0be2dc421be4fcd0172e5afceea3970e2f3d940', $resource->getSha1());
    }

    /**
     * @test
     */
    public function setSha1AcceptsUppercaseHashesAndNormalizesThemToLowercase()
    {
        $resource = new PersistentResource();
        $resource->setSha1('D0BE2DC421BE4fCD0172E5AFCEEA3970E2f3d940');
        self::assertSame('d0be2dc421be4fcd0172e5afceea3970e2f3d940', $resource->getSha1());
    }
}
