<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * Testcase for the File class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ResourceTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFilenameStoresTheFileExtensionInLowerCase() {
		$resource = new \F3\FLOW3\Resource\Resource();
		$resource->setFilename('Something.Jpeg');
		$this->assertSame('jpeg', $resource->getFileExtension());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMimeTypeReturnsMimeTypeBasedOnFileExtension() {
		$resource = new \F3\FLOW3\Resource\Resource();
		$resource->setFilename('file.jpg');
		$this->assertSame('image/jpeg', $resource->getMimeType());

		$resource = new \F3\FLOW3\Resource\Resource();
		$resource->setFilename('file.zip');
		$this->assertSame('application/x-zip-compressed', $resource->getMimeType());

		$resource = new \F3\FLOW3\Resource\Resource();
		$resource->setFilename('file.someunknownextension');
		$this->assertSame('application/octet-stream', $resource->getMimeType());
	}
}

?>
