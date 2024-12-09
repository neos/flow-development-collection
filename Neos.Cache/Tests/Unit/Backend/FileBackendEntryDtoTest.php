<?php
namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

use Neos\Cache\Backend\FileBackendEntryDto;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Test case for the FileBackendEntryDto
 */
class FileBackendEntryDtoTest extends BaseTestCase
{
    /**
     */
    public static function validEntryConstructorParameters(): array
    {
        return [
            ['data', [], 0],
            ['data', [], time() + 100],
            ['data', [], time() - 100],
            ['data', [], time()],
            ['', ['tag1'], time()],
            ['data', ['tag1'], time()],
            ['data', ['tag1', 'tag2'], time()],
        ];
    }

    /**
     * @dataProvider validEntryConstructorParameters
     * @test
     */
    public function canBeCreatedWithConstructor(string $data, array $tags, int $expiryTime): void
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        self::assertInstanceOf(FileBackendEntryDto::class, $entryDto);
    }

    /**
     * @dataProvider validEntryConstructorParameters
     * @test
     */
    public function gettersReturnDataProvidedToConstructor(string $data, array $tags, int $expiryTime): void
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        self::assertEquals($data, $entryDto->getData());
        self::assertEquals($tags, $entryDto->getTags());
        self::assertEquals($expiryTime, $entryDto->getExpiryTime());
    }

    /**
     * @test
     */
    public function isExpiredReturnsFalseIfExpiryTimeIsInFuture(): void
    {
        $entryDto = new FileBackendEntryDto('data', [], time() + 10);
        self::assertFalse($entryDto->isExpired());
    }

    /**
     * @test
     */
    public function isExpiredReturnsTrueIfExpiryTimeIsInPast(): void
    {
        $entryDto = new FileBackendEntryDto('data', [], time() - 10);
        self::assertTrue($entryDto->isExpired());
    }

    /**
     * @dataProvider validEntryConstructorParameters
     * @test
     */
    public function isIdempotent(string $data, array $tags, int $expiryTime): void
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        $entryString = (string)$entryDto;
        $entryDtoReconstituted = FileBackendEntryDto::fromString($entryString);
        $entryStringFromReconstituted = (string)$entryDtoReconstituted;
        self::assertEquals($entryString, $entryStringFromReconstituted);
        self::assertEquals($data, $entryDtoReconstituted->getData());
        self::assertEquals($tags, $entryDtoReconstituted->getTags());
        self::assertEquals($expiryTime, $entryDtoReconstituted->getExpiryTime());
    }
}
