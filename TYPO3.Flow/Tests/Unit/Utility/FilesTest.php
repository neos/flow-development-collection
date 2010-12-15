<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Utility;

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
 * Testcase for the Utility Files class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FilesTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithoutSlashes() {
		$path = 'foobar';
		$this->assertEquals('foobar', \F3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithForwardSlashes() {
		$path = 'foo/bar/test/';
		$this->assertEquals('foo/bar/test/', \F3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithBackwardSlashes() {
		$path = 'foo\\bar\\test\\';
		$this->assertEquals('foo/bar/test/', \F3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithForwardAndBackwardSlashes() {
		$path = 'foo/bar\\test/';
		$this->assertEquals('foo/bar/test/', \F3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForEmptyPath() {
		$this->assertEquals('', \F3\FLOW3\Utility\Files::concatenatePaths(array()));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForOnePath() {
		$this->assertEquals('foo', \F3\FLOW3\Utility\Files::concatenatePaths(array('foo')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForTwoPath() {
		$this->assertEquals('foo/bar', \F3\FLOW3\Utility\Files::concatenatePaths(array('foo', 'bar')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForPathsWithLeadingSlash() {
		$this->assertEquals('/foo/bar', \F3\FLOW3\Utility\Files::concatenatePaths(array('/foo', 'bar')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForPathsWithTrailingSlash() {
		$this->assertEquals('foo/bar', \F3\FLOW3\Utility\Files::concatenatePaths(array('foo', 'bar/')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForPathsWithLeadingAndTrailingSlash() {
		$this->assertEquals('/foo/bar/bar/foo', \F3\FLOW3\Utility\Files::concatenatePaths(array('/foo/bar/', '/bar/foo/')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForBrokenPaths() {
		$this->assertEquals('/foo/bar/bar', \F3\FLOW3\Utility\Files::concatenatePaths(array('\\foo/bar\\', '\\bar')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForEmptyPathArrayElements() {
		$this->assertEquals('foo/bar', \F3\FLOW3\Utility\Files::concatenatePaths(array('foo', '', 'bar')));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithDriveLetterAndBackwardSlashes() {
		$path = 'c:\\foo\\bar\\test\\';
		$this->assertEquals('c:/foo/bar/test/', \F3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function pathsWithProtocol() {
		return array(
			array('file:///foo\\bar', 'file:///foo/bar'),
			array('vfs:///foo\\bar', 'vfs:///foo/bar'),
			array('phar:///foo\\bar', 'phar:///foo/bar')
		);
	}

	/**
	 * @test
	 * @dataProvider pathsWithProtocol
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithProtocol($path, $expected) {
		$this->assertEquals($expected, \F3\FLOW3\Utility\Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function is_linkReturnsFalseForNonExistingFiles() {
		$this->assertFalse(\F3\FLOW3\Utility\Files::is_link('NonExistingPath'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function is_linkReturnsFalseForExistingFileThatIsNoSymlink() {
		$targetPathAndFilename = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3FilesTestFile'));
		file_put_contents($targetPathAndFilename, 'some data');
		$this->assertFalse(\F3\FLOW3\Utility\Files::is_link($targetPathAndFilename));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function is_linkReturnsTrueForExistingSymlink() {
		$targetPathAndFilename = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3FilesTestFile'));
		file_put_contents($targetPathAndFilename, 'some data');
		$linkPathAndFilename = \F3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3FilesTestLink'));
		if (file_exists($linkPathAndFilename)) {
			unlink($linkPathAndFilename);
		}
		symlink($targetPathAndFilename, $linkPathAndFilename);
		$this->assertTrue(\F3\FLOW3\Utility\Files::is_link($linkPathAndFilename));
	}
}
?>