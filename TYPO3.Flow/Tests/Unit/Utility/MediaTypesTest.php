<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\MediaTypes;

/**
 * Testcase for the Utility Media Types class
 */
class MediaTypesTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data Provider
	 */
	public function filenamesAndMediaTypes() {
		return array(
			array('', 'application/octet-stream'),
			array('foo', 'application/octet-stream'),
			array('foo.bar', 'application/octet-stream'),
			array('index.html', 'text/html'),
			array('video.mov', 'video/quicktime'),
		);
	}

	/**
	 * @test
	 * @dataProvider filenamesAndMediaTypes
	 */
	public function getMediaTypeFromFilenameMapsFilenameOrExtensionToMediaType($filename, $expectedMediaType) {
		$this->assertSame($expectedMediaType, MediaTypes::getMediaTypeFromFilename($filename));
	}

	/**
	 * Data Provider
	 */
	public function mediaTypesAndFilenames() {
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
	public function getFilenameExtensionFromMediaTypeReturnsFirstFileExtensionFoundForThatMediaType($mediaType, $filenameExtensions) {
		$this->assertSame(($filenameExtensions === array() ? '' : $filenameExtensions[0]), MediaTypes::getFilenameExtensionFromMediaType($mediaType));
	}

	/**
	 * @test
	 * @dataProvider mediaTypesAndFilenames
	 */
	public function getFilenameExtensionsFromMediaTypeReturnsAllFileExtensionForThatMediaType($mediaType, $filenameExtensions) {
		$this->assertSame($filenameExtensions, MediaTypes::getFilenameExtensionsFromMediaType($mediaType));
	}


	/**
	 * Data provider with media types and their parsed counterparts
	 */
	public function mediaTypesAndParsedPieces() {
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
	public function parseMediaTypeReturnsAssociativeArrayWithIndividualPartsOfTheMediaType($mediaType, $expectedPieces) {
		$request = $this->getAccessibleMock('TYPO3\Flow\Http\Request', array('dummy'), array(), '', FALSE);
		$actualPieces = MediaTypes::parseMediaType($mediaType);
		$this->assertSame($expectedPieces, $actualPieces);
	}

	/**
	 * Data provider
	 */
	public function mediaRangesAndMatchingOrNonMatchingMediaTypes() {
		return array(
			array('invalid', 'text/html', FALSE),
			array('text/html', 'text/html', TRUE),
			array('text/html', 'text/plain', FALSE),
			array('*/*', 'text/html', TRUE),
			array('*/*', 'application/json', TRUE),
			array('text/*', 'text/html', TRUE),
			array('text/*', 'text/plain', TRUE),
			array('text/*', 'application/xml', FALSE),
			array('application/*', 'application/xml', TRUE),
			array('text/x-dvi', 'text/x-dvi', TRUE),
			array('-Foo.+/~Bar199', '-Foo.+/~Bar199', TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider mediaRangesAndMatchingOrNonMatchingMediaTypes
	 */
	public function mediaRangeMatchesChecksIfTheGivenMediaRangeMatchesTheGivenMediaType($mediaRange, $mediaType, $expectedResult) {
		$actualResult = MediaTypes::mediaRangeMatches($mediaRange, $mediaType);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * Data provider with media types and their trimmed versions
	 */
	public function mediaTypesWithAndWithoutParameters() {
		return array(
			array('text/html', 'text/html'),
			array('application/json; charset=UTF-8', 'application/json'),
			array('application/vnd.org.flow.coffee+json; kind =Arabica;weight= 15g;  sugar =none', 'application/vnd.org.flow.coffee+json'),
			array('invalid', NULL),
			array('invalid/', NULL),
		);
	}

	/**
	 * @test
	 * @dataProvider mediaTypesWithAndWithoutParameters
	 */
	public function trimMediaTypeReturnsJustTheTypeAndSubTypeWithoutParameters($mediaType, $expectedResult) {
		$actualResult = MediaTypes::trimMediaType($mediaType);
		$this->assertSame($expectedResult, $actualResult);
	}


}
?>