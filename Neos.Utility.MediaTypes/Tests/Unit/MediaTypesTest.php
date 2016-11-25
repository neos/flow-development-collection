<?php
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

use Neos\Flow\Http\Request;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\MediaTypes;

/**
 * Testcase for the Utility Media Types class
 */
class MediaTypesTest extends UnitTestCase
{
    /**
     * Data Provider
     */
    public function filenamesAndMediaTypes()
    {
        return [
            ['', 'application/octet-stream'],
            ['foo', 'application/octet-stream'],
            ['foo.bar', 'application/octet-stream'],
            ['index.html', 'text/html'],
            ['video.mov', 'video/quicktime'],
            ['image.jpeg', 'image/jpeg'],
            ['image.jpg', 'image/jpeg'],
            ['image.JPG', 'image/jpeg'],
            ['image.JPEG', 'image/jpeg'],
        ];
    }

    /**
     * @test
     * @dataProvider filenamesAndMediaTypes
     */
    public function getMediaTypeFromFilenameMapsFilenameOrExtensionToMediaType($filename, $expectedMediaType)
    {
        $this->assertSame($expectedMediaType, MediaTypes::getMediaTypeFromFilename($filename));
    }

    /**
     * Data Provider
     */
    public function filesAndMediaTypes()
    {
        return [
            ['', 'application/octet-stream'],
            ['Text.txt', 'text/plain'],
            ['Neos.png', 'image/png'],
        ];
    }

    /**
     * @test
     * @dataProvider filesAndMediaTypes
     */
    public function getMediaTypeFromFilename($filename, $expectedMediaType)
    {
        $filePath = __DIR__ . '/Fixtures/' . $filename;
        $fileContent = file_exists($filePath) ? file_get_contents($filePath) : '';
        $this->assertSame($expectedMediaType, MediaTypes::getMediaTypeFromFileContent($fileContent));
    }

    /**
     * Data Provider
     */
    public function mediaTypesAndFilenames()
    {
        return [
            ['foo/bar', []],
            ['application/octet-stream', ['bin', 'dms', 'lrf', 'mar', 'so', 'dist', 'distz', 'pkg', 'bpk', 'dump', 'elc', 'deploy']],
            ['text/html', ['html', 'htm']],
            ['text/csv', ['csv']],
        ];
    }

    /**
     * @test
     * @dataProvider mediaTypesAndFilenames
     */
    public function getFilenameExtensionFromMediaTypeReturnsFirstFileExtensionFoundForThatMediaType($mediaType, $filenameExtensions)
    {
        $this->assertSame(($filenameExtensions === [] ? '' : $filenameExtensions[0]), MediaTypes::getFilenameExtensionFromMediaType($mediaType));
    }

    /**
     * @test
     * @dataProvider mediaTypesAndFilenames
     */
    public function getFilenameExtensionsFromMediaTypeReturnsAllFileExtensionForThatMediaType($mediaType, $filenameExtensions)
    {
        $this->assertSame($filenameExtensions, MediaTypes::getFilenameExtensionsFromMediaType($mediaType));
    }


    /**
     * Data provider with media types and their parsed counterparts
     */
    public function mediaTypesAndParsedPieces()
    {
        return [
            ['text/html', ['type' => 'text', 'subtype' => 'html', 'parameters' => []]],
            ['application/json; charset=UTF-8', ['type' => 'application', 'subtype' => 'json', 'parameters' => ['charset' => 'UTF-8']]],
            ['application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', ['type' => 'application', 'subtype' => 'vnd.org.flow.coffee+json', 'parameters' => ['kind' => 'Arabica', 'weight' => '15g', 'sugar' => 'none']]],
        ];
    }

    /**
     * @test
     * @dataProvider mediaTypesAndParsedPieces
     */
    public function parseMediaTypeReturnsAssociativeArrayWithIndividualPartsOfTheMediaType($mediaType, $expectedPieces)
    {
        $request = $this->getAccessibleMock(Request::class, ['dummy'], [], '', false);
        $actualPieces = MediaTypes::parseMediaType($mediaType);
        $this->assertSame($expectedPieces, $actualPieces);
    }

    /**
     * Data provider
     */
    public function mediaRangesAndMatchingOrNonMatchingMediaTypes()
    {
        return [
            ['invalid', 'text/html', false],
            ['text/html', 'text/html', true],
            ['text/html', 'text/plain', false],
            ['*/*', 'text/html', true],
            ['*/*', 'application/json', true],
            ['text/*', 'text/html', true],
            ['text/*', 'text/plain', true],
            ['text/*', 'application/xml', false],
            ['application/*', 'application/xml', true],
            ['text/x-dvi', 'text/x-dvi', true],
            ['-Foo.+/~Bar199', '-Foo.+/~Bar199', true],
        ];
    }

    /**
     * @test
     * @dataProvider mediaRangesAndMatchingOrNonMatchingMediaTypes
     */
    public function mediaRangeMatchesChecksIfTheGivenMediaRangeMatchesTheGivenMediaType($mediaRange, $mediaType, $expectedResult)
    {
        $actualResult = MediaTypes::mediaRangeMatches($mediaRange, $mediaType);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Data provider with media types and their trimmed versions
     */
    public function mediaTypesWithAndWithoutParameters()
    {
        return [
            ['text/html', 'text/html'],
            ['application/json; charset=UTF-8', 'application/json'],
            ['application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', 'application/vnd.org.flow.coffee+json'],
            ['invalid', null],
            ['invalid/', null],
        ];
    }

    /**
     * @test
     * @dataProvider mediaTypesWithAndWithoutParameters
     */
    public function trimMediaTypeReturnsJustTheTypeAndSubTypeWithoutParameters($mediaType, $expectedResult)
    {
        $actualResult = MediaTypes::trimMediaType($mediaType);
        $this->assertSame($expectedResult, $actualResult);
    }
}
