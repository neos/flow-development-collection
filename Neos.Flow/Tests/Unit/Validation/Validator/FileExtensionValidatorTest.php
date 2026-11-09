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
use Neos\Flow\Validation\Validator\FileExtensionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the file extension validator
 *
 */
final class FileExtensionValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = FileExtensionValidator::class;

    public function setUp(): void
    {
        $this->validatorOptions([
            'allowedExtensions' => ['jpg','jpeg','png'],
        ]);
    }

    protected function createResourceMetaDataInterfaceMock(string $filename): ResourceMetaDataInterface
    {
        $mock = $this->createMock(ResourceMetaDataInterface::class);
        $mock->expects($this->once())->method('getFilename')->willReturn($filename);
        return $mock;
    }

    protected function createUploadedFileInterfaceMock(string $filename): UploadedFileInterface
    {
        $mock = $this->createMock(UploadedFileInterface::class);
        $mock->expects($this->once())->method('getClientFilename')->willReturn($filename);
        return $mock;
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

    public static function itemsWithAllowedExtension(): \Iterator
    {
        yield ['resource', 'image.jpg'];
        yield ['resource', 'image.jpeg'];
        yield ['resource', 'image.png'];
        yield ['uploaded', 'image.jpg'];
        yield ['uploaded', 'image.jpeg'];
        yield ['uploaded', 'image.png'];
    }

    #[DataProvider('itemsWithAllowedExtension')]
    #[Test]
    public function validateAcceptsItemsWithAllowedExtension(string $type, string $filename)
    {
        $item = $type === 'resource'
            ? $this->createResourceMetaDataInterfaceMock($filename)
            : $this->createUploadedFileInterfaceMock($filename);
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithDisallowedExtension(): \Iterator
    {
        yield ['resource', 'evil.exe'];
        yield ['resource', 'image.tiff'];
        yield ['uploaded', 'evil.exe'];
        yield ['uploaded', 'image.tiff'];
    }

    #[DataProvider('itemsWithDisallowedExtension')]
    #[Test]
    public function validateRejectsItemsWithDisallowedExtension(string $type, string $filename)
    {
        $item = $type === 'resource'
            ? $this->createResourceMetaDataInterfaceMock($filename)
            : $this->createUploadedFileInterfaceMock($filename);
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
