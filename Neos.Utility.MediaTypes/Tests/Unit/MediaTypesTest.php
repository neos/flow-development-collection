<?php

declare(strict_types=1);

namespace Neos\Utility\MediaTypes\Tests\Unit;

/*
 * This file is part of the Neos.Utility.MediaTypes package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Utility\MediaTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the Utility Media Types class
 */
final class MediaTypesTest extends TestCase
{
    /**
     * Data Provider
     */
    public static function filenamesAndMediaTypes(): \Iterator
    {
        yield ['', 'application/octet-stream'];
        yield ['foo', 'application/octet-stream'];
        yield ['foo.bar', 'application/octet-stream'];
        yield ['index.html', 'text/html'];
        yield ['video.mov', 'video/quicktime'];
        yield ['image.jpeg', 'image/jpeg'];
        yield ['image.jpg', 'image/jpeg'];
        yield ['image.JPG', 'image/jpeg'];
        yield ['image.JPEG', 'image/jpeg'];
    }

    #[DataProvider('filenamesAndMediaTypes')]
    #[Test]
    public function getMediaTypeFromFilenameMapsFilenameOrExtensionToMediaType(string $filename, string $expectedMediaType): void
    {
        self::assertSame($expectedMediaType, MediaTypes::getMediaTypeFromFilename($filename));
    }

    /**
     * Data Provider
     */
    public static function filesAndMediaTypes(): \Iterator
    {
        yield ['', 'application/octet-stream'];
        yield ['Text.txt', 'text/plain'];
        yield ['Neos.png', 'image/png'];
    }

    #[DataProvider('filesAndMediaTypes')]
    #[Test]
    public function getMediaTypeFromFileContent(string $filename, string $expectedMediaType): void
    {
        $filePath = __DIR__ . '/Fixtures/' . $filename;
        $fileContent = is_file($filePath) ? file_get_contents($filePath) : '';
        self::assertSame($expectedMediaType, MediaTypes::getMediaTypeFromFileContent($fileContent));
    }

    /**
     * Data Provider
     */
    public static function mediaTypesAndFilenames(): \Iterator
    {
        yield ['foo/bar', []];
        yield ['application/octet-stream', ['bin', 'dms', 'lrf', 'mar', 'so', 'dist', 'distz', 'pkg', 'bpk', 'dump', 'elc', 'deploy']];
        yield ['text/html', ['html', 'htm']];
        yield ['text/csv', ['csv']];
    }

    #[DataProvider('mediaTypesAndFilenames')]
    #[Test]
    public function getFilenameExtensionFromMediaTypeReturnsFirstFileExtensionFoundForThatMediaType(string $mediaType, array $filenameExtensions): void
    {
        self::assertSame(($filenameExtensions === [] ? '' : $filenameExtensions[0]), MediaTypes::getFilenameExtensionFromMediaType($mediaType));
    }

    #[DataProvider('mediaTypesAndFilenames')]
    #[Test]
    public function getFilenameExtensionsFromMediaTypeReturnsAllFileExtensionForThatMediaType(string $mediaType, array $filenameExtensions): void
    {
        self::assertSame($filenameExtensions, MediaTypes::getFilenameExtensionsFromMediaType($mediaType));
    }


    /**
     * Data provider with media types and their parsed counterparts
     */
    public static function mediaTypesAndParsedPieces(): \Iterator
    {
        yield ['text/html', ['type' => 'text', 'subtype' => 'html', 'parameters' => []]];
        yield ['application/json; charset=UTF-8', ['type' => 'application', 'subtype' => 'json', 'parameters' => ['charset' => 'UTF-8']]];
        yield ['application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', ['type' => 'application', 'subtype' => 'vnd.org.flow.coffee+json', 'parameters' => ['kind' => 'Arabica', 'weight' => '15g', 'sugar' => 'none']]];
    }

    #[DataProvider('mediaTypesAndParsedPieces')]
    #[Test]
    public function parseMediaTypeReturnsAssociativeArrayWithIndividualPartsOfTheMediaType(string $mediaType, array $expectedPieces): void
    {
        $actualPieces = MediaTypes::parseMediaType($mediaType);
        self::assertSame($expectedPieces, $actualPieces);
    }

    /**
     * Data provider
     */
    public static function mediaRangesAndMatchingOrNonMatchingMediaTypes(): \Iterator
    {
        yield ['invalid', 'text/html', false];
        yield ['text/html', 'text/html', true];
        yield ['text/html', 'text/plain', false];
        yield ['*/*', 'text/html', true];
        yield ['*/*', 'application/json', true];
        yield ['text/*', 'text/html', true];
        yield ['text/*', 'text/plain', true];
        yield ['text/*', 'application/xml', false];
        yield ['application/*', 'application/xml', true];
        yield ['text/x-dvi', 'text/x-dvi', true];
        yield ['-Foo.+/~Bar199', '-Foo.+/~Bar199', true];
    }

    #[DataProvider('mediaRangesAndMatchingOrNonMatchingMediaTypes')]
    #[Test]
    public function mediaRangeMatchesChecksIfTheGivenMediaRangeMatchesTheGivenMediaType(string $mediaRange, string $mediaType, bool $expectedResult): void
    {
        $actualResult = MediaTypes::mediaRangeMatches($mediaRange, $mediaType);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * Data provider with media types and their trimmed versions
     */
    public static function mediaTypesWithAndWithoutParameters(): \Iterator
    {
        yield ['text/html', 'text/html'];
        yield ['application/json; charset=UTF-8', 'application/json'];
        yield ['application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', 'application/vnd.org.flow.coffee+json'];
        yield ['invalid', null];
        yield ['invalid/', null];
    }

    #[DataProvider('mediaTypesWithAndWithoutParameters')]
    #[Test]
    public function trimMediaTypeReturnsJustTheTypeAndSubTypeWithoutParameters(string $mediaType, ?string $expectedResult = null): void
    {
        $actualResult = MediaTypes::trimMediaType($mediaType);
        self::assertSame($expectedResult, $actualResult);
    }
}
