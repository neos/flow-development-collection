<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the Utility Files class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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