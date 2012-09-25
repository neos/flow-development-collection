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

}
?>