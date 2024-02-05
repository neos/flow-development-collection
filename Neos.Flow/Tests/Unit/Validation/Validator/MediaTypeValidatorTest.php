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
use Neos\Flow\Validation\Validator\MediaTypeValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the media type validator
 *
 */
class MediaTypeValidatorTest extends AbstractValidatorTestcase
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

    public function emptyItems(): array
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


    public function itemsWithAllowedMediaType(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock('image/jpeg')],
            [$this->createResourceMetaDataInterfaceMock('application/csv')],
            [$this->createUploadedFileInterfaceMock('image/jpeg')],
            [$this->createUploadedFileInterfaceMock('application/csv')]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithAllowedMediaType
     */
    public function validateAcceptsItemsWithAllowedMediaType($item)
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public function itemsWithUnhandledTypes(): array
    {
        return [
            [12],
            ['hello'],
            [(object) []],
            [new \DateTime()]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithUnhandledTypes
     */
    public function validateRejectsItemsWithUnhandledTypes($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }



    public function itemsWithDisallowedMediaType(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock('video/mp4')],
            [$this->createResourceMetaDataInterfaceMock('application/pdf')],
            [$this->createUploadedFileInterfaceMock('video/mp4')],
            [$this->createUploadedFileInterfaceMock('application/pdf')],
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithDisallowedMediaType
     */
    public function validateRejectsItemsWithDisallowedMediaType($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }

    public function itemsWithOtherMediaType(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock('text/plain')],
            [$this->createUploadedFileInterfaceMock('text/plain')],
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithOtherMediaType
     */
    public function validateRejectsItemsWithOtherMediaType($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
