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

require_once('vfs/vfsStream.php');

/**
 * Testcase for the Utility Environment class
 *
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
	public function getRequestUriReturnsExpectedUriWhenUsingPlainRequests() {
		$expectedUriString = 'http://flow3.typo3.org/is/the/base/for/typo3?5=0';
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->SAPIName = 'apache';
		$environment->SERVER = array(
			'HTTP_HOST' => 'flow3.typo3.org',
			'REQUEST_URI' => '/index.php/is/the/base/for/typo3?5=0'
			);
			$returnedUriString = (string)$environment->getRequestUri();
			$this->assertEquals($expectedUriString, $returnedUriString, 'The URI returned did not match the expected value.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRequestUriReturnsExpectedUriWhenUsingRewriteStyleRequests() {
		$expectedUriString = 'http://flow3.typo3.org/is/the/base/for/typo3?5=0';
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->SAPIName = 'apache';
		$environment->SERVER = array(
			'HTTP_HOST' => 'flow3.typo3.org',
			'REQUEST_URI' => '/is/the/base/for/typo3?5=0'
			);
			$returnedUriString = (string)$environment->getRequestUri();
			$this->assertEquals($expectedUriString, $returnedUriString, 'The URI returned did not match the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getUploadedFilesJustReturnsThePreviouslyUntangledFILESVariable() {
		$environment = new \F3\FLOW3\Utility\MockEnvironment();
		$environment->FILES = array('foo' => 'bar');
		$this->assertEquals(array('foo' => 'bar'), $environment->getUploadedFiles());
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
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSAPINameReturnsNotNullOnFreshlyConstructedEnvironment() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$this->assertNotNull($environment->getSAPIName());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getMaximumPathLengthReturnsCorrectValue() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$expectedValue = PHP_MAXPATHLEN;
		if ((integer)$expectedValue <= 0) {
			$this->fail('The PHP Constant PHP_MAXPATHLEN is not available on your system! Please file a PHP bug report.');
		}
		$this->assertEquals($expectedValue, $environment->getMaximumPathLength());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function createTemporaryDirectoryLogsToSystemLogIfTemporaryDirectoryBaseIsLongComparedToMaximumPathLength() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('t1'));
		$path = \vfsStream::url('t1') . '/some/temporary/path/';

		$environment = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Utility\Environment'), array('getMaximumPathLength'), array(), '', FALSE);
		$environment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(248));

		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');
		$mockSystemLogger->expects($this->once())->method('log')->with('The path to your temporary directory is ' . strlen($path) . ' characters long. The maximum path length of your system is only 248. Please consider setting the temporaryDirectoryBase option to a shorter path.', LOG_WARNING);
		$environment->injectSystemLogger($mockSystemLogger);

		$environment->_call('createTemporaryDirectory', $path);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function createTemporaryDirectoryDoesNotLogToSystemLogIfTemporaryDirectoryBaseIsShortComparedToMaximumPathLength() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('t2'));
		$path = \vfsStream::url('t2') . '/some/temporary/path/';

		$environment = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Utility\Environment'), array('getMaximumPathLength'), array(), '', FALSE);
		$environment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(500));

		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');
		$mockSystemLogger->expects($this->never())->method('log');
		$environment->injectSystemLogger($mockSystemLogger);

		$environment->_call('createTemporaryDirectory', $path);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function untangleFilesArrayTransformsTheFilesSuperglobalIntoAMangeableForm() {
		$convolutedFiles = array (
			'a0' => array (
				'name' => array (
					'a1' => 'a.txt',
				),
				'type' => array (
					'a1' => 'text/plain',
				),
				'tmp_name' => array (
					'a1' => '/private/var/tmp/phpbqXsYt',
				),
				'error' => array (
					'a1' => 0,
				),
				'size' => array (
					'a1' => 100,
				),
			),
			'b0' => array (
				'name' => array (
					'b1' => 'b.txt',
				),
				'type' => array (
					'b1' => 'text/plain',
				),
				'tmp_name' => array (
					'b1' => '/private/var/tmp/phpvZ6oUD',
				),
				'error' => array (
					'b1' => 0,
				),
				'size' => array (
					'b1' => 200,
				),
			),
			'c' => array (
				'name' => 'c.txt',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpS9KMNw',
				'error' => 0,
				'size' => 300,
			),
			'd0' => array (
				'name' => array (
					'd1' => array (
						'd2' => array (
							'd3' => 'd.txt',
						),
					),
				),
				'type' => array (
					'd1' => array(
						'd2' => array (
							'd3' => 'text/plain',
							),
						),
					),
				'tmp_name' => array (
					'd1' => array (
						'd2' => array(
							'd3' => '/private/var/tmp/phprR3fax',
						),
					),
				),
				'error' => array (
					'd1' => array (
						'd2' => array(
							'd3' => 0,
						),
					),
				),
				'size' => array (
					'd1' => array (
						'd2' => array(
							'd3' => 400,
						),
					),
				),
			),
			'e0' => array (
				'name' => array (
					'e1' => array (
						'e2' => array (
							0 => 'e_one.txt',
							1 => 'e_two.txt',
						),
					),
				),
				'type' => array (
					'e1' => array (
						'e2' => array (
							0 => 'text/plain',
							1 => 'text/plain',
						),
					),
				),
				'tmp_name' => array (
					'e1' => array (
						'e2' => array (
							0 => '/private/var/tmp/php01fitB',
							1 => '/private/var/tmp/phpUUB2cv',
						),
					),
				),
				'error' => array (
					'e1' => array (
						'e2' => array (
							0 => 0,
							1 => 0,
						),
					),
				),
				'size' => array (
					'e1' => array (
						'e2' => array (
							0 => 510,
							1 => 520,
						)
					)
				)
			)
		);

		$untangledFiles = array (
			'a0' => array (
				'a1' => array(
					'name' => 'a.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpbqXsYt',
					'error' => 0,
					'size' => 100,
				),
			),
			'b0' => array (
				'b1' => array(
					'name' => 'b.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpvZ6oUD',
					'error' => 0,
					'size' => 200,
				)
			),
			'c' => array (
				'name' => 'c.txt',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpS9KMNw',
				'error' => 0,
				'size' => 300,
			),
			'd0' => array (
				'd1' => array(
					'd2' => array(
						'd3' => array(
							'name' => 'd.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/phprR3fax',
							'error' => 0,
							'size' => 400,
						),
					),
				),
			),
			'e0' => array (
				'e1' => array(
					'e2' => array(
						0 => array(
							'name' => 'e_one.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/php01fitB',
							'error' => 0,
							'size' => 510,
						),
						1 => array(
							'name' => 'e_two.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/phpUUB2cv',
							'error' => 0,
							'size' => 520,
						)
					)
				)
			)
		);

		$environment = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Utility\Environment'), array('dummy'), array(), '', FALSE);
		$result = $environment->_call('untangleFilesArray', $convolutedFiles);

		$this->assertSame($untangledFiles, $result);
	}
}
?>