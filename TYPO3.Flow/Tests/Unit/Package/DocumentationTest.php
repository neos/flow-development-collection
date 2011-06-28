<?php
namespace TYPO3\FLOW3\Tests\Unit\Package;

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
 * Testcase for the package documentation class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DocumentationTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Sets up this test case
	 *
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function constructSetsPackageNameAndPathToDocumentation() {
		$documentationPath = \vfsStream::url('testDirectory') . '/';

		$mockPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');

		$documentation = new \TYPO3\FLOW3\Package\Documentation($mockPackage, 'Manual', $documentationPath);

		$this->assertSame($mockPackage, $documentation->getPackage());
		$this->assertEquals('Manual', $documentation->getDocumentationName());
		$this->assertEquals($documentationPath, $documentation->getDocumentationPath());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getDocumentationFormatsScansDocumentationDirectoryAndReturnsDocumentationFormatObjectsIndexedByFormatName() {
		$documentationPath = \vfsStream::url('testDirectory') . '/';

		$mockPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($documentationPath . 'DocBook/en');

		$documentation = new \TYPO3\FLOW3\Package\Documentation($mockPackage, 'Manual', $documentationPath);
		$documentationFormats = $documentation->getDocumentationFormats();

		$this->assertEquals('DocBook', $documentationFormats['DocBook']->getFormatName());
	}
}
?>