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
 * Testcase for the Resource Pointer class
 *
 */
class ResourcePointerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructThrowsExceptionOnFormallyInvalidHash()
    {
        new \TYPO3\Flow\Resource\ResourcePointer('69e73da3ce0ad08c717b7b9f1c759182d64');
    }

    /**
     * @test
     */
    public function getHashReturnsTheResourceHash()
    {
        $hash = '69e73da3ce0ad08c717b7b9f1c759182d6650944';
        $resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
        $this->assertSame($hash, $resourcePointer->getHash());
    }

    /**
     * @test
     */
    public function toStringReturnsTheResourceHashObject()
    {
        $hash = '69e73da3ce0ad08c717b7b9f1c759182d6650944';

        $resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
        $this->assertSame($hash, (string)$resourcePointer);
    }
}
