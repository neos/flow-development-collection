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

/**
 * Testcase for the Resource Pointer class
 *
 */
class ResourcePointerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructThrowsExceptionOnFormallyInvalidHash() {
		new \TYPO3\Flow\Resource\ResourcePointer('69e73da3ce0ad08c717b7b9f1c759182d64');
	}

	/**
	 * @test
	 */
	public function getHashReturnsTheResourceHash() {
		$hash = '69e73da3ce0ad08c717b7b9f1c759182d6650944';
		$resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
		$this->assertSame($hash, $resourcePointer->getHash());
	}

	/**
	 * @test
	 */
	public function toStringReturnsTheResourceHashObject() {
		$hash = '69e73da3ce0ad08c717b7b9f1c759182d6650944';

		$resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
		$this->assertSame($hash, (string)$resourcePointer);
	}
}

?>