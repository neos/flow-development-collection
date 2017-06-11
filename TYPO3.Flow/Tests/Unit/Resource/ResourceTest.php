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

use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Resource class
 */
class ResourceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setFilenameStoresTheFileExtensionInLowerCase()
    {
        $resource = new Resource();
        $resource->setFilename('Something.Jpeg');
        $this->assertSame('jpeg', $resource->getFileExtension());
        $this->assertSame('Something.jpeg', $resource->getFilename());
    }

    /**
     * @test
     */
    public function setFilenameSetsTheMediaType()
    {
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
    public function setFilenameDoesNotAppendFileExtensionIfItIsEmpty()
    {
        $resource = new Resource();
        $resource->setFilename('FileWithoutExtension');
        $this->assertSame('', $resource->getFileExtension());
        $this->assertSame('FileWithoutExtension', $resource->getFilename());
    }

    /**
     * @test
     */
    public function getMediaTypeReturnsMediaTypeBasedOnFileExtension()
    {
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

    /**
     * @return array
     */
    public function invalidSha1Values()
    {
        return [
          [''],
          [null],
          ['XYZE2DC421BE4fCD0172E5AFCEEA3970E2f3d940'],
          [new \stdClass()],
          [false],
        ];
    }

    /**
     * @test
     * @dataProvider invalidSha1Values
     * @expectedException \InvalidArgumentException
     */
    public function setSha1RejectsInvalidValues($invalidValue)
    {
        $resource = new Resource();
        $resource->setSha1($invalidValue);
        $this->assertSame('d0be2dc421be4fcd0172e5afceea3970e2f3d940', $resource->getSha1());
    }

    /**
     * @test
     */
    public function setSha1AcceptsUppercaseHashesAndNormalizesThemToLowercase()
    {
        $resource = new Resource();
        $resource->setSha1('D0BE2DC421BE4fCD0172E5AFCEEA3970E2f3d940');
        $this->assertSame('d0be2dc421be4fcd0172e5afceea3970e2f3d940', $resource->getSha1());
    }
}
