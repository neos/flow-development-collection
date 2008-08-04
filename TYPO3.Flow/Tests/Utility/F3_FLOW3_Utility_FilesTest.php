<?php
declare(ENCODING = 'utf-8');

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
class F3_FLOW3_Utility_FilesTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithoutSlashes() {
		$path = 'foobar';
		$this->assertEquals('foobar', F3_FLOW3_Utility_Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithForwardSlashes() {
		$path = 'foo/bar/test/';
		$this->assertEquals('foo/bar/test/', F3_FLOW3_Utility_Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithBackwardSlashes() {
		$path = 'foo\\bar\\test\\';
		$this->assertEquals('foo/bar/test/', F3_FLOW3_Utility_Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUnixStylePathWorksForPathWithForwardAndBackwardSlashes() {
		$path = 'foo/bar\\test/';
		$this->assertEquals('foo/bar/test/', F3_FLOW3_Utility_Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForEmptyPath() {
		$this->assertEquals('', F3_FLOW3_Utility_Files::concatenatePaths(array()));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForOnePath() {
		$this->assertEquals('foo', F3_FLOW3_Utility_Files::concatenatePaths(array('foo')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForTwoPath() {
		$this->assertEquals('foo/bar', F3_FLOW3_Utility_Files::concatenatePaths(array('foo', 'bar')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForPathsWithLeadingSlash() {
		$this->assertEquals('/foo/bar', F3_FLOW3_Utility_Files::concatenatePaths(array('/foo', 'bar')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForPathsWithTrailingSlash() {
		$this->assertEquals('foo/bar', F3_FLOW3_Utility_Files::concatenatePaths(array('foo', 'bar/')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForPathsWithLeadingAndTrailingSlash() {
		$this->assertEquals('/foo/bar/bar/foo', F3_FLOW3_Utility_Files::concatenatePaths(array('/foo/bar/', '/bar/foo/')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForBrokenPaths() {
		$this->assertEquals('/foo/bar/bar', F3_FLOW3_Utility_Files::concatenatePaths(array('\\foo/bar\\', '\\bar')));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function concatenatePathsWorksForEmptyPathArrayElements() {
		$this->assertEquals('foo/bar', F3_FLOW3_Utility_Files::concatenatePaths(array('foo', '', 'bar')));
	}
}
?>