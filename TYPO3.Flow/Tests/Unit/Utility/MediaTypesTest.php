<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Utility\MediaTypes;

/**
 * Testcase for the Utility Media Types class
 */
class MediaTypesTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Data Provider
     */
    public function filenamesAndMediaTypes()
    {
        return array(
            array('', 'application/octet-stream'),
            array('foo', 'application/octet-stream'),
            array('foo.bar', 'application/octet-stream'),
            array('index.html', 'text/html'),
            array('video.mov', 'video/quicktime'),
            array('image.jpeg', 'image/jpeg'),
            array('image.jpg', 'image/jpeg'),
            array('image.JPG', 'image/jpeg'),
            array('image.JPEG', 'image/jpeg'),
        );
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
    public function mediaTypesAndFilenames()
    {
        return array(
            array('foo/bar', array()),
            array('application/octet-stream', array('bin', 'dms', 'lrf', 'mar', 'so', 'dist', 'distz', 'pkg', 'bpk', 'dump', 'elc', 'deploy')),
            array('text/html', array('html', 'htm')),
            array('text/csv', array('csv')),
        );
    }

    /**
     * @test
     * @dataProvider mediaTypesAndFilenames
     */
    public function getFilenameExtensionFromMediaTypeReturnsFirstFileExtensionFoundForThatMediaType($mediaType, $filenameExtensions)
    {
        $this->assertSame(($filenameExtensions === array() ? '' : $filenameExtensions[0]), MediaTypes::getFilenameExtensionFromMediaType($mediaType));
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
        return array(
            array('text/html', array('type' => 'text', 'subtype' => 'html', 'parameters' => array())),
            array('application/json; charset=UTF-8', array('type' => 'application', 'subtype' => 'json', 'parameters' => array('charset' => 'UTF-8'))),
            array('application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', array('type' => 'application', 'subtype' => 'vnd.org.flow.coffee+json', 'parameters' => array('kind' => 'Arabica', 'weight' => '15g', 'sugar' => 'none'))),
        );
    }

    /**
     * @test
     * @dataProvider mediaTypesAndParsedPieces
     */
    public function parseMediaTypeReturnsAssociativeArrayWithIndividualPartsOfTheMediaType($mediaType, $expectedPieces)
    {
        $request = $this->getAccessibleMock(\TYPO3\Flow\Http\Request::class, array('dummy'), array(), '', false);
        $actualPieces = MediaTypes::parseMediaType($mediaType);
        $this->assertSame($expectedPieces, $actualPieces);
    }

    /**
     * Data provider
     */
    public function mediaRangesAndMatchingOrNonMatchingMediaTypes()
    {
        return array(
            array('invalid', 'text/html', false),
            array('text/html', 'text/html', true),
            array('text/html', 'text/plain', false),
            array('*/*', 'text/html', true),
            array('*/*', 'application/json', true),
            array('text/*', 'text/html', true),
            array('text/*', 'text/plain', true),
            array('text/*', 'application/xml', false),
            array('application/*', 'application/xml', true),
            array('text/x-dvi', 'text/x-dvi', true),
            array('-Foo.+/~Bar199', '-Foo.+/~Bar199', true),
        );
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
        return array(
            array('text/html', 'text/html'),
            array('application/json; charset=UTF-8', 'application/json'),
            array('application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', 'application/vnd.org.flow.coffee+json'),
            array('invalid', null),
            array('invalid/', null),
        );
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
