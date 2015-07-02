<?php
namespace TYPO3\Flow\Tests\Unit\Resource\Streams;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\Target\FileSystemTarget;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Tests for the FileSystemTarget class
 */
class FileSystemTargetTest extends UnitTestCase {

	/**
	 * @var FileSystemTarget
	 */
	protected $fileSystemTarget;

	public function setUp() {
		$this->fileSystemTarget = new FileSystemTarget('test');
	}

	/**
	 * @test
	 */
	public function getNameReturnsTargetName() {
		$this->assertSame('test', $this->fileSystemTarget->getName());
	}

	/**
	 * @return array
	 */
	public function getPublicStaticResourceUriDataProvider() {
		return [
			['baseUri' => 'http://localhost/', 'relativePathAndFilename' => 'SomeFilename.jpg', 'expectedResult' => 'http://localhost/SomeFilename.jpg'],
			['baseUri' => 'http://localhost/', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => 'http://localhost/some/path/SomeFilename.jpg'],
			['baseUri' => 'relative/', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => 'relative/some/path/SomeFilename.jpg'],
			['baseUri' => 'relative/', 'relativePathAndFilename' => 'some/pa th/Some Filename.jpg', 'expectedResult' => 'relative/some/pa%20th/Some%20Filename.jpg'],
		];
	}

	/**
	 * @test
	 * @dataProvider getPublicStaticResourceUriDataProvider
	 * @param string $baseUri
	 * @param string $relativePathAndFilename
	 * @param string $expectedResult
	 */
	public function getPublicStaticResourceUriTests($baseUri, $relativePathAndFilename, $expectedResult) {
		$this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
		$this->assertSame($expectedResult, $this->fileSystemTarget->getPublicStaticResourceUri($relativePathAndFilename));
	}

	/**
	 * @return array
	 */
	public function getPublicPersistentResourceUriDataProvider() {
		return [
			['baseUri' => 'http://localhost/', 'relativePublicationPath' => 'some/path/', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/some/path/SomeFilename.jpg'],
			['baseUri' => 'http://localhost/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'],
			['baseUri' => 'http://localhost/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'],
			['baseUri' => 'http://localhost/', 'relativePublicationPath' => 'so me/path/', 'filename' => 'Some Filename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/so%20me/path/Some%20Filename.jpg'],
		];
	}

	/**
	 * @test
	 * @dataProvider getPublicPersistentResourceUriDataProvider
	 * @param string $baseUri
	 * @param string $relativePublicationPath
	 * @param string $filename
	 * @param string $sha1
	 * @param string $expectedResult
	 */
	public function getPublicPersistentResourceUriTests($baseUri, $relativePublicationPath, $filename, $sha1, $expectedResult) {
		$this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
		/** @var Resource|\PHPUnit_Framework_MockObject_MockObject $mockResource */
		$mockResource = $this->getMockBuilder(Resource::class)->disableOriginalConstructor()->getMock();
		$mockResource->expects($this->any())->method('getRelativePublicationPath')->will($this->returnValue($relativePublicationPath));
		$mockResource->expects($this->any())->method('getFilename')->will($this->returnValue($filename));
		$mockResource->expects($this->any())->method('getSha1')->will($this->returnValue($sha1));

		$this->assertSame($expectedResult, $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
	}

}