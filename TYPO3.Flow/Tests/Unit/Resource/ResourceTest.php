<?php
namespace TYPO3\FLOW3\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the File class
 *
 */
class ResourceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function setFilenameStoresTheFileExtensionInLowerCase() {
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('Something.Jpeg');
		$this->assertSame('jpeg', $resource->getFileExtension());
		$this->assertSame('Something.jpeg', $resource->getFilename());
	}

	/**
	 * @test
	 */
	public function setFilenameDoesNotAppendFileExtensionIfItIsEmpty() {
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('FileWithoutExtension');
		$this->assertSame('', $resource->getFileExtension());
		$this->assertSame('FileWithoutExtension', $resource->getFilename());
	}

	/**
	 * @test
	 */
	public function getMimeTypeReturnsMimeTypeBasedOnFileExtension() {
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('file.jpg');
		$this->assertSame('image/jpeg', $resource->getMimeType());

		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('file.zip');
		$this->assertSame('application/x-zip-compressed', $resource->getMimeType());

		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('file.someunknownextension');
		$this->assertSame('application/octet-stream', $resource->getMimeType());
	}
}

?>
