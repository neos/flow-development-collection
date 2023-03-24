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
    public function validEntryConstructorParameters()
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
     *
     * @param string $data
     * @param array $tags
     * @param int $expiryTime
     * @return void
     */
    public function canBeCreatedWithConstructor($data, $tags, $expiryTime)
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        self::assertInstanceOf(FileBackendEntryDto::class, $entryDto);
    }

    /**
     * @dataProvider validEntryConstructorParameters
     * @test
     *
     * @param $data
     * @param $tags
     * @param $expiryTime
     * @return void
     */
    public function gettersReturnDataProvidedToConstructor($data, $tags, $expiryTime)
    {
        $entryDto = new FileBackendEntryDto($data, $tags, $expiryTime);
        self::assertEquals($data, $entryDto->getData());
        self::assertEquals($tags, $entryDto->getTags());
        self::assertEquals($expiryTime, $entryDto->getExpiryTime());
    }

    /**
     * @test
     * @return void
     */
    public function isExpiredReturnsFalseIfExpiryTimeIsInFuture()
    {
        $entryDto = new FileBackendEntryDto('data', [], time() + 10);
        self::assertFalse($entryDto->isExpired());
    }

    /**
     * @test
     * @return void
     */
    public function isExpiredReturnsTrueIfExpiryTimeIsInPast()
    {
        $entryDto = new FileBackendEntryDto('data', [], time() - 10);
        self::assertTrue($entryDto->isExpired());
    }

    /**
     * @dataProvider validEntryConstructorParameters
     * @test
     * @return void
     */
    public function isIdempotent($data, $tags, $expiryTime)
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
