<?php
namespace TYPO3\FLOW3\Tests\Unit\Resource;

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
 * Testcase for the Resource class
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
	public function getMediaTypeReturnsMediaTypeBasedOnFileExtension() {
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('file.jpg');
		$this->assertSame('image/jpeg', $resource->getMediaType());

		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('file.zip');
		$this->assertSame('application/zip', $resource->getMediaType());

		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setFilename('file.someunknownextension');
		$this->assertSame('application/octet-stream', $resource->getMediaType());
	}

	/**
	 * @test
	 */
	public function getUriReturnsResourceWrapperUri() {
		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('fakeSha1'));
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setResourcePointer($mockResourcePointer);
		$this->assertEquals('resource://fakeSha1', $resource->getUri());
	}

	/**
	 * @test
	 */
	public function toStringReturnsResourcePointerStringRepresentation() {
		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('fakeSha1'));
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setResourcePointer($mockResourcePointer);
		$this->assertEquals('fakeSha1', (string) $resource);
	}
}

?>
