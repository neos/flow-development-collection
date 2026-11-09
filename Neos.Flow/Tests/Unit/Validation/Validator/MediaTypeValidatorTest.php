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
use Neos\Flow\Validation\Validator\MediaTypeValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the media type validator
 *
 */
final class MediaTypeValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = MediaTypeValidator::class;

    public function setUp(): void
    {
        $this->validatorOptions([
            'allowedTypes' => ['image/*', 'application/csv'],
            'disallowedTypes' => ['video/*', 'application/pdf']
        ]);
    }

    protected function createResourceMetaDataInterfaceMock(string $mediaType): ResourceMetaDataInterface
    {
        $mock = $this->createMock(ResourceMetaDataInterface::class);
        $mock->expects($this->once())->method('getMediaType')->willReturn($mediaType);
        return $mock;
    }

    protected function createUploadedFileInterfaceMock(string $mediaType): UploadedFileInterface
    {
        $mock = $this->createMock(UploadedFileInterface::class);
        $mock->expects($this->once())->method('getClientMediaType')->willReturn($mediaType);
        return $mock;
    }

    public static function emptyItems(): \Iterator
    {
        yield [null];
        yield [''];
    }

    #[DataProvider('emptyItems')]
    #[Test]
    public function validateAcceptsEmptyValue($item): void
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithAllowedMediaType(): \Iterator
    {
        yield ['resource', 'image/jpeg'];
        yield ['resource', 'application/csv'];
        yield ['uploaded', 'image/jpeg'];
        yield ['uploaded', 'application/csv'];
    }

    #[DataProvider('itemsWithAllowedMediaType')]
    #[Test]
    public function validateAcceptsItemsWithAllowedMediaType(string $type, string $mediaType): void
    {
        $item = $type === 'resource'
            ? $this->createResourceMetaDataInterfaceMock($mediaType)
            : $this->createUploadedFileInterfaceMock($mediaType);
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithUnhandledTypes(): \Iterator
    {
        yield [12];
        yield ['hello'];
        yield [(object) []];
        yield [new \DateTime()];
    }

    #[DataProvider('itemsWithUnhandledTypes')]
    #[Test]
    public function validateRejectsItemsWithUnhandledTypes($item): void
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithDisallowedMediaType(): \Iterator
    {
        yield ['resource', 'video/mp4'];
        yield ['resource', 'application/pdf'];
        yield ['uploaded', 'video/mp4'];
        yield ['uploaded', 'application/pdf'];
    }

    #[DataProvider('itemsWithDisallowedMediaType')]
    #[Test]
    public function validateRejectsItemsWithDisallowedMediaType(string $type, string $mediaType): void
    {
        $item = $type === 'resource'
            ? $this->createResourceMetaDataInterfaceMock($mediaType)
            : $this->createUploadedFileInterfaceMock($mediaType);
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public static function itemsWithOtherMediaType(): \Iterator
    {
        yield ['resource', 'text/plain'];
        yield ['uploaded', 'text/plain'];
    }

    #[DataProvider('itemsWithOtherMediaType')]
    #[Test]
    public function validateRejectsItemsWithOtherMediaType(string $type, string $mediaType): void
    {
        $item = $type === 'resource'
            ? $this->createResourceMetaDataInterfaceMock($mediaType)
            : $this->createUploadedFileInterfaceMock($mediaType);
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
