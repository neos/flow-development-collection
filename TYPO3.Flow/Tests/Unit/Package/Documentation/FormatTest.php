<?php
namespace TYPO3\FLOW3\Tests\Unit\Package\Documentation;

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
 * Testcase for the documentation format class
 *
 */
class FormatTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

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
	public function constructSetsNameAndPathToFormat() {
		$documentationPath = \vfsStream::url('testDirectory') . '/';

		$format = new \TYPO3\FLOW3\Package\Documentation\Format('DocBook', $documentationPath);

		$this->assertEquals('DocBook', $format->getFormatName());
		$this->assertEquals($documentationPath, $format->getFormatPath());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getLanguagesScansFormatDirectoryAndReturnsLanguagesAsStrings() {
		$formatPath = \vfsStream::url('testDirectory') . '/';

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($formatPath . 'en');

		$format = new \TYPO3\FLOW3\Package\Documentation\Format('DocBook', $formatPath);
		$availableLanguages = $format->getAvailableLanguages();

		$this->assertEquals(array('en'), $availableLanguages);
	}
}
?>