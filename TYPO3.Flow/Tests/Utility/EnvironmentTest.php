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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the Utility Environment class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class EnvironmentTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathToTemporaryDirectoryReturnsPathWithTrailingSlash() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertEquals('/', substr($path, -1, 1), 'The temporary path did not end with slash.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathToTemporaryDirectoryReturnsAnExistingPath() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertTrue(file_exists($path), 'The temporary path does not exist.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getScriptPathAndFilenameReturnsCorrectPathAndFilename() {
		$expectedPathAndFilename = '/this/is/the/file.php';
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->SERVER = array(
			'SCRIPT_FILENAME' => '/this/is/the/file.php'
		);
		$returnedPathAndFilename = $environment->getScriptPathAndFilename();
		$this->assertEquals($expectedPathAndFilename, $returnedPathAndFilename, 'The returned path did not match the expected value.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getScriptPathAndFilenameReturnsCorrectPathAndFilenameForWindowsStylePath() {
		$expectedPathAndFilename = '/this/is/the/file.php';
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->SERVER = array(
			'SCRIPT_FILENAME' => '\\this\\is\\the\\file.php'
		);
		$returnedPathAndFilename = $environment->getScriptPathAndFilename();
		$this->assertEquals($expectedPathAndFilename, $returnedPathAndFilename, 'The returned path did not match the expected value.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRequestURIReturnsExpectedURIWhenUsingPathInfo() {
		$expectedURIString = 'http://flow3.typo3.org/is/the/base/for/typo3?5=0';
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->SERVER = array(
			'HTTP_HOST' => 'flow3.typo3.org',
			'QUERY_STRING' => '5=0',
			'PATH_INFO' => '/is/the/base/for/typo3'
		);
		$returnedURIString = (string)$environment->getRequestURI();
		$this->assertEquals($expectedURIString, $returnedURIString, 'The URI returned did not match the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawServerEnvironmentJustReturnsTheSERVERVariable() {
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->SERVER = array('foo' => 'bar');
		$this->assertEquals(array('foo' => 'bar'), $environment->getRawServerEnvironment());
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function SAPINamesAndTypes() {
		return array(
			array('apache', 'Web'),
			array('isapi', 'Web'),
			array('cgi', 'Web'),
			array('cli', 'CLI'),
		);
	}

	/**
	 * @test
	 * @dataProvider SAPINamesAndTypes
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSAPITypeReturnsTheNormalizedSAPIName($SAPIName, $normalizedSAPIName) {
		$environment = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Utility\Environment'), array('dummy'), array(), '', FALSE);

		$environment->_set('SAPIName', $SAPIName);
		$this->assertSame($normalizedSAPIName, $environment->getSAPIType());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSAPINameReturnsNotNullOnFreshlyConstructedEnvironment() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$this->assertNotNull($environment->getSAPIName());
	}
}
?>