<?php
namespace Neos\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ResourceManagement\ResourceMetaDataInterface;
use Neos\Flow\Validation\Validator\FileSizeValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the file size validator
 *
 */
class FileSizeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = FileSizeValidator::class;

    public function setUp(): void
    {
        $this->validatorOptions([
            'minimum' => 200,
            'maximum' => 1000,
        ]);
    }

    protected static function createResourceMetaDataInterfaceMock(int $filesize): ResourceMetaDataInterface
    {
        return new class ($filesize) implements ResourceMetaDataInterface {
            public function __construct(protected int $filesize)
            {
            }
            public function setFilename($filename)
            {
            }

            public function getFilename()
            {
            }

            public function getFileSize()
            {
                return $this->filesize;
            }

            public function setFileSize($fileSize)
            {
            }

            public function setRelativePublicationPath($path)
            {
            }

            public function getRelativePublicationPath()
            {
            }

            public function getMediaType()
            {
            }

            public function getSha1()
            {
            }

            public function setSha1($sha1)
            {
            }

        };
    }

    protected static function createUploadedFileInterfaceMock(string $filesize): UploadedFileInterface
    {
        return new class ($filesize) implements UploadedFileInterface {
            public function __construct(protected int $filesize)
            {
            }

            public function getStream()
            {
            }

            public function moveTo(string $targetPath)
            {
            }

            public function getSize()
            {
                return $this->filesize;
            }

            public function getError()
            {
            }

            public function getClientFilename()
            {
            }

            public function getClientMediaType()
            {
            }
        };
    }

    public static function emptyItems(): array
    {
        return [
            [null],
            ['']
        ];
    }

    /**
     * @test
     * @dataProvider emptyItems
     */
    public function validateAcceptsEmptyValue($item)
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithAllowedSize(): array
    {
        return [
            [self::createResourceMetaDataInterfaceMock(200)],
            [self::createResourceMetaDataInterfaceMock(800)],
            [self::createResourceMetaDataInterfaceMock(1000)],
            [self::createUploadedFileInterfaceMock(200)],
            [self::createUploadedFileInterfaceMock(800)],
            [self::createUploadedFileInterfaceMock(1000)]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithAllowedSize
     */
    public function validateAcceptsItemsWithAllowedSize($item)
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithLargerThanAllowedSize(): array
    {
        return [
            [self::createResourceMetaDataInterfaceMock(1001)],
            [self::createResourceMetaDataInterfaceMock(PHP_INT_MAX)],
            [self::createUploadedFileInterfaceMock(1001)],
            [self::createUploadedFileInterfaceMock(PHP_INT_MAX)]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithLargerThanAllowedSize
     */
    public function validateRejectsItemsWithLargerThanAllowedSize($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithSmallerThanAllowedSize(): array
    {
        return [
            [self::createResourceMetaDataInterfaceMock(199)],
            [self::createResourceMetaDataInterfaceMock(0)],
            [self::createUploadedFileInterfaceMock(199)],
            [self::createUploadedFileInterfaceMock(0)]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithSmallerThanAllowedSize
     */
    public function validateRejectsItemsWithSmallerThanAllowedSize($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
