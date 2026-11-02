<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

use Neos\Cache\Backend\FileBackendEntryDto;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for the FileBackendEntryDto
 */
final class FileBackendEntryDtoTest extends BaseTestCase
{
    /**
     */
    public static function validEntryConstructorParameters(): \Iterator
    {
        yield ['data', [], 0];
        yield ['data', [], time() + 100];
        yield ['data', [], time() - 100];
        yield ['data', [], time()];
        yield ['', ['tag1'], time()];
        yield ['data', ['tag1'], time()];
        yield ['data', ['tag1', 'tag2'], time()];
    }

    #[DataProvider('validEntryConstructorParameters')]
    #[Test]
    public function canBeCreatedWithConstructor(string $data, array $tags, int $expiryTime): void
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        self::assertInstanceOf(FileBackendEntryDto::class, $entryDto);
    }

    #[DataProvider('validEntryConstructorParameters')]
    #[Test]
    public function gettersReturnDataProvidedToConstructor(string $data, array $tags, int $expiryTime): void
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        self::assertSame($data, $entryDto->getData());
        self::assertEquals($tags, $entryDto->getTags());
        self::assertSame($expiryTime, $entryDto->getExpiryTime());
    }

    #[Test]
    public function isExpiredReturnsFalseIfExpiryTimeIsInFuture(): void
    {
        $entryDto = new FileBackendEntryDto('data', [], time() + 10);
        self::assertFalse($entryDto->isExpired());
    }

    #[Test]
    public function isExpiredReturnsTrueIfExpiryTimeIsInPast(): void
    {
        $entryDto = new FileBackendEntryDto('data', [], time() - 10);
        self::assertTrue($entryDto->isExpired());
    }

    #[DataProvider('validEntryConstructorParameters')]
    #[Test]
    public function isIdempotent(string $data, array $tags, int $expiryTime): void
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        $entryString = (string)$entryDto;
        $entryDtoReconstituted = FileBackendEntryDto::fromString($entryString);
        $entryStringFromReconstituted = (string)$entryDtoReconstituted;
        self::assertSame($entryString, $entryStringFromReconstituted);
        self::assertSame($data, $entryDtoReconstituted->getData());
        self::assertEquals($tags, $entryDtoReconstituted->getTags());
        self::assertSame($expiryTime, $entryDtoReconstituted->getExpiryTime());
    }
}
