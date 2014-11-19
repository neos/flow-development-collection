<?php
namespace TYPO3\Flow\Tests\Unit\Resource;

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
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Resource class
 */
class ResourceTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function setFilenameStoresTheFileExtensionInLowerCase() {
		$resource = new Resource();
		$resource->setFilename('Something.Jpeg');
		$this->assertSame('jpeg', $resource->getFileExtension());
		$this->assertSame('Something.jpeg', $resource->getFilename());
	}

	/**
	 * @test
	 */
	public function setFilenameSetsTheMediaType() {
		$resource = new Resource();

		$resource->setFilename('Something.jpg');
		$this->assertSame('image/jpeg', $resource->getMediaType());

		$resource->setFilename('Something.png');
		$this->assertSame('image/png', $resource->getMediaType());

		$resource->setFilename('Something.Jpeg');
		$this->assertSame('image/jpeg', $resource->getMediaType());
	}

	/**
	 * @test
	 */
	public function setFilenameDoesNotAppendFileExtensionIfItIsEmpty() {
		$resource = new Resource();
		$resource->setFilename('FileWithoutExtension');
		$this->assertSame('', $resource->getFileExtension());
		$this->assertSame('FileWithoutExtension', $resource->getFilename());
	}

	/**
	 * @test
	 */
	public function getMediaTypeReturnsMediaTypeBasedOnFileExtension() {
		$resource = new Resource();
		$resource->setFilename('file.jpg');
		$this->assertSame('image/jpeg', $resource->getMediaType());

		$resource = new Resource();
		$resource->setFilename('file.zip');
		$this->assertSame('application/zip', $resource->getMediaType());

		$resource = new Resource();
		$resource->setFilename('file.someunknownextension');
		$this->assertSame('application/octet-stream', $resource->getMediaType());
	}

}
