<?php
namespace TYPO3\Flow\Tests\Unit\Resource;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
