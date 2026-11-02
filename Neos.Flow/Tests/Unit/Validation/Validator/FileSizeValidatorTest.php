<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the file size validator
 *
 */
final class FileSizeValidatorTest extends AbstractValidatorTestcase
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

    protected static function createUploadedFileInterfaceMock(int $filesize): UploadedFileInterface
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

    public static function emptyItems(): \Iterator
    {
        yield [null];
        yield [''];
    }

    #[DataProvider('emptyItems')]
    #[Test]
    public function validateAcceptsEmptyValue($item)
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithAllowedSize(): \Iterator
    {
        yield [self::createResourceMetaDataInterfaceMock(200)];
        yield [self::createResourceMetaDataInterfaceMock(800)];
        yield [self::createResourceMetaDataInterfaceMock(1000)];
        yield [self::createUploadedFileInterfaceMock(200)];
        yield [self::createUploadedFileInterfaceMock(800)];
        yield [self::createUploadedFileInterfaceMock(1000)];
    }

    #[DataProvider('itemsWithAllowedSize')]
    #[Test]
    public function validateAcceptsItemsWithAllowedSize($item)
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithLargerThanAllowedSize(): \Iterator
    {
        yield [self::createResourceMetaDataInterfaceMock(1001)];
        yield [self::createResourceMetaDataInterfaceMock(PHP_INT_MAX)];
        yield [self::createUploadedFileInterfaceMock(1001)];
        yield [self::createUploadedFileInterfaceMock(PHP_INT_MAX)];
    }

    #[DataProvider('itemsWithLargerThanAllowedSize')]
    #[Test]
    public function validateRejectsItemsWithLargerThanAllowedSize($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithSmallerThanAllowedSize(): \Iterator
    {
        yield [self::createResourceMetaDataInterfaceMock(199)];
        yield [self::createResourceMetaDataInterfaceMock(0)];
        yield [self::createUploadedFileInterfaceMock(199)];
        yield [self::createUploadedFileInterfaceMock(0)];
    }

    #[DataProvider('itemsWithSmallerThanAllowedSize')]
    #[Test]
    public function validateRejectsItemsWithSmallerThanAllowedSize($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
