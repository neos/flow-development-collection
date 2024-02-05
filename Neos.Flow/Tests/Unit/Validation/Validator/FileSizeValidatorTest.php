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

    protected function createResourceMetaDataInterfaceMock(int $filesize): ResourceMetaDataInterface
    {
        $mock = $this->createMock(ResourceMetaDataInterface::class);
        $mock->expects($this->once())->method('getFileSize')->willReturn($filesize);
        return $mock;
    }

    protected function createUploadedFileInterfaceMock(string $filesize): UploadedFileInterface
    {
        $mock = $this->createMock(UploadedFileInterface::class);
        $mock->expects($this->once())->method('getSize')->willReturn($filesize);
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

    public function itemsWithAllowedSize(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock(200)],
            [$this->createResourceMetaDataInterfaceMock(800)],
            [$this->createResourceMetaDataInterfaceMock(1000)],
            [$this->createUploadedFileInterfaceMock(200)],
            [$this->createUploadedFileInterfaceMock(800)],
            [$this->createUploadedFileInterfaceMock(1000)]
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

    public function itemsWithLargerThanAllowedSize(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock(1001)],
            [$this->createResourceMetaDataInterfaceMock(PHP_INT_MAX)],
            [$this->createUploadedFileInterfaceMock(1001)],
            [$this->createUploadedFileInterfaceMock(PHP_INT_MAX)]
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

    public function itemsWithSmallerThanAllowedSize(): array
    {
        return [
            [$this->createResourceMetaDataInterfaceMock(199)],
            [$this->createResourceMetaDataInterfaceMock(0)],
            [$this->createUploadedFileInterfaceMock(199)],
            [$this->createUploadedFileInterfaceMock(0)]
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
