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
use Neos\Flow\Validation\Validator\FileExtensionValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Testcase for the file extension validator
 *
 */
class FileExtensionValidatorTest extends AbstractValidatorTestcase
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

    public function itemsWithAllowedExtension(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock('image.jpg')],
            [$this->createResourceMetaDataInterfaceMock('image.jpeg')],
            [$this->createResourceMetaDataInterfaceMock('image.png')],
            [$this->createUploadedFileInterfaceMock('image.jpg')],
            [$this->createUploadedFileInterfaceMock('image.jpeg')],
            [$this->createUploadedFileInterfaceMock('image.png')]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithAllowedExtension
     */
    public function validateAcceptsItemsWithAllowedExtension($item)
    {
        self::assertFalse($this->validator->validate($item)->hasErrors());
    }

    public function itemsWithDisallowedExtension(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock('evil.exe')],
            [$this->createResourceMetaDataInterfaceMock('image.tiff')],
            [$this->createUploadedFileInterfaceMock('evil.exe')],
            [$this->createUploadedFileInterfaceMock('image.tiff')]
        ];
    }

    /**
     * @test
     * @dataProvider itemsWithDisallowedExtension
     */
    public function validateRejectsItemsWithDisallowedExtension($item)
    {
        self::assertTrue($this->validator->validate($item)->hasErrors());
    }
}
