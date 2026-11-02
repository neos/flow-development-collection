<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for the PersistentResource class
 */
final class PersistentResourceTest extends UnitTestCase
{
    #[Test]
    public function setFilenameStoresTheFileExtensionInLowerCase()
    {
        $resource = new PersistentResource();
        $resource->setFilename('Something.Jpeg');
        self::assertSame('jpeg', $resource->getFileExtension());
        self::assertSame('Something.jpeg', $resource->getFilename());
    }

    #[Test]
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

    #[Test]
    public function setFilenameDoesNotAppendFileExtensionIfItIsEmpty()
    {
        $resource = new PersistentResource();
        $resource->setFilename('FileWithoutExtension');
        self::assertSame('', $resource->getFileExtension());
        self::assertSame('FileWithoutExtension', $resource->getFilename());
    }

    #[Test]
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
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidSha1Values(): \Iterator
    {
        yield [''];
        yield [null];
        yield ['XYZE2DC421BE4fCD0172E5AFCEEA3970E2f3d940'];
        yield [new \stdClass()];
        yield [false];
    }

    #[DataProvider('invalidSha1Values')]
    #[Test]
    public function setSha1RejectsInvalidValues($invalidValue)
    {
        $this->expectException(\InvalidArgumentException::class);
        $resource = new PersistentResource();
        $resource->setSha1($invalidValue);
        self::assertSame('d0be2dc421be4fcd0172e5afceea3970e2f3d940', $resource->getSha1());
    }

    #[Test]
    public function setSha1AcceptsUppercaseHashesAndNormalizesThemToLowercase()
    {
        $resource = new PersistentResource();
        $resource->setSha1('D0BE2DC421BE4fCD0172E5AFCEEA3970E2f3d940');
        self::assertSame('d0be2dc421be4fcd0172e5afceea3970e2f3d940', $resource->getSha1());
    }
}
