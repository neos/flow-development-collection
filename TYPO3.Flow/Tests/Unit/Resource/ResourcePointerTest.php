<?php
namespace F3\FLOW3\Tests\Unit\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Resource class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ResourcePointerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsExceptionOnFormallyInvalidHash() {
		$resourcePointer = new \F3\FLOW3\Resource\ResourcePointer('69e73da3ce0ad08c717b7b9f1c759182d64');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHashReturnsTheResourceHash() {
		$hash = '69e73da3ce0ad08c717b7b9f1c759182d6650944';
		$resourcePointer = new \F3\FLOW3\Resource\ResourcePointer($hash);
		$this->assertSame($hash, $resourcePointer->getHash());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toStringReturnsTheResourceHashObject() {
		$hash = '69e73da3ce0ad08c717b7b9f1c759182d6650944';

		$resourcePointer = new \F3\FLOW3\Resource\ResourcePointer($hash);
		$this->assertSame($hash, (string)$resourcePointer);
	}
}

?>