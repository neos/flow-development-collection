<?php
namespace TYPO3\Flow\Tests\Unit\Resource;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the Resource class
 *
 */
class ResourceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function setFilenameStoresTheFileExtensionInLowerCase()
    {
        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setFilename('Something.Jpeg');
        $this->assertSame('jpeg', $resource->getFileExtension());
        $this->assertSame('Something.jpeg', $resource->getFilename());
    }

    /**
     * @test
     */
    public function setFilenameDoesNotAppendFileExtensionIfItIsEmpty()
    {
        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setFilename('FileWithoutExtension');
        $this->assertSame('', $resource->getFileExtension());
        $this->assertSame('FileWithoutExtension', $resource->getFilename());
    }

    /**
     * @test
     */
    public function getMediaTypeReturnsMediaTypeBasedOnFileExtension()
    {
        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setFilename('file.jpg');
        $this->assertSame('image/jpeg', $resource->getMediaType());

        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setFilename('file.zip');
        $this->assertSame('application/zip', $resource->getMediaType());

        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setFilename('file.someunknownextension');
        $this->assertSame('application/octet-stream', $resource->getMediaType());
    }

    /**
     * @test
     */
    public function getUriReturnsResourceWrapperUri()
    {
        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('fakeSha1'));
        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setResourcePointer($mockResourcePointer);
        $this->assertEquals('resource://fakeSha1', $resource->getUri());
    }

    /**
     * @test
     */
    public function toStringReturnsResourcePointerStringRepresentation()
    {
        $mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false);
        $mockResourcePointer->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('fakeSha1'));
        $resource = new \TYPO3\Flow\Resource\Resource();
        $resource->setResourcePointer($mockResourcePointer);
        $this->assertEquals('fakeSha1', (string) $resource);
    }
}
