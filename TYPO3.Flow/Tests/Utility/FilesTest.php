<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FilesTest extends \F3\Testing\BaseTestCase {

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
}
?>