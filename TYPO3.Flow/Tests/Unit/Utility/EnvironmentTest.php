<?php
namespace TYPO3\FLOW3\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Utility Environment class
 *
 */
class EnvironmentTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getPathToTemporaryDirectoryReturnsPathWithTrailingSlash() {
		$environment = new \TYPO3\FLOW3\Utility\Environment('Testing');
		$environment->setTemporaryDirectoryBase(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3EnvironmentTest')));
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertEquals('/', substr($path, -1, 1), 'The temporary path did not end with slash.');
	}

	/**
	 * @test
	 */
	public function getPathToTemporaryDirectoryReturnsAnExistingPath() {
		$environment = new \TYPO3\FLOW3\Utility\Environment('Testing');
		$environment->setTemporaryDirectoryBase(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3EnvironmentTest')));

		$path = $environment->getPathToTemporaryDirectory();
		$this->assertTrue(file_exists($path), 'The temporary path does not exist.');
	}

	/**
	 * @test
	 */
	public function getScriptPathAndFilenameReturnsCorrectPathAndFilename() {
		$expectedPathAndFilename = '/this/is/the/file.php';
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array('Testing'));
		$environment->_set('SERVER', array(
			'SCRIPT_FILENAME' => '/this/is/the/file.php'
			)
		);
		$returnedPathAndFilename = $environment->getScriptPathAndFilename();
		$this->assertEquals($expectedPathAndFilename, $returnedPathAndFilename, 'The returned path did not match the expected value.');
	}

	/**
	 * @test
	 */
	public function getScriptPathAndFilenameReturnsCorrectPathAndFilenameForWindowsStylePath() {
		$expectedPathAndFilename = '/this/is/the/file.php';
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array('Testing'));
		$environment->_set('SERVER', array(
			'SCRIPT_FILENAME' => '\\this\\is\\the\\file.php'
			)
		);
		$returnedPathAndFilename = $environment->getScriptPathAndFilename();
		$this->assertEquals($expectedPathAndFilename, $returnedPathAndFilename, 'The returned path did not match the expected value.');
	}

	/**
	 * @test
	 */
	public function getScriptRequestPathReturnsCorrectPath() {
		$expectedPath = '/blog/Web/';
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array('Testing'));
		$environment->_set('SERVER', array(
				'SCRIPT_NAME' => '/blog/Web/index.php'
			)
		);
		$returnedPath = $environment->getScriptRequestPath();
		$this->assertEquals($expectedPath, $returnedPath);
	}

	/**
	 */
	public function requestUriServerVariableArrayPairs() {
		return array(
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/is/the/base/for/typo3?5=0'),
				array(
					'HTTP_HOST' => 'flow3.typo3.org',
					'SCRIPT_NAME' => '/index.php',
					'REQUEST_URI' => '/index.php/is/the/base/for/typo3?5=0'
				)
			),
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/Web/is/the/base/for/typo3?5=0'),
				array(
					'HTTP_HOST' => 'flow3.typo3.org',
					'SCRIPT_NAME' => '/Web/index.php',
					'REQUEST_URI' => '/Web/index.php/is/the/base/for/typo3?5=0'
				),
			),
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/is/the/base/for/typo3?5=0'),
				array(
					'HTTP_HOST' => 'flow3.typo3.org',
					'SCRIPT_NAME' => '/index.php',
					'REQUEST_URI' => '/is/the/base/for/typo3?5=0'
				)
			),
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/Web/is/the/base/for/typo3?5=0'),
				array(
					'HTTP_HOST' => 'flow3.typo3.org',
					'SCRIPT_NAME' => '/Web/index.php',
					'REQUEST_URI' => '/Web/is/the/base/for/typo3?5=0'
				)
			),
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/dev/flow3/blog/Web/posts/index'),
				array(
					'HTTP_HOST' => 'flow3.typo3.org',
					'SCRIPT_NAME' => '/dev/flow3/blog/Web/index.php',
					'REQUEST_URI' => '/dev/flow3/blog/Web/posts/index'
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider requestUriServerVariableArrayPairs
	 */
	public function getRequestUriReturnsExpectedUri($expectedUri, $SERVER) {
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array('Testing'));
		$environment->_set('SAPIName', 'apache');
		$environment->_set('SERVER', $SERVER);

		$returnedUriString = (string)$environment->getRequestUri();
		$this->assertEquals((string)$expectedUri, $returnedUriString, 'The URI returned did not match the expected value.');
	}

	/**
	 */
	public function requestUriBaseUriScriptNameTuples() {
		return array(
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/is/the/base/for/typo3?5=0'),
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/'),
				'/index.php'
			),
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/Web/is/the/base/for/typo3?5=0'),
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/Web/'),
				'/Web/index.php'
			),
			array(
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/cms/Web/is/the/base/for/typo3?5=0'),
				new \TYPO3\FLOW3\Http\Uri('http://flow3.typo3.org/cms/Web/'),
				'/cms/Web/index.php'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider requestUriBaseUriScriptNameTuples
	 */
	public function detectBaseUriRendersExpectedUriWhenUsingPlainRequests($requestUri, $expectedBaseUri, $SCRIPT_NAME) {
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('getRequestUri'), array('Testing'));
		$environment->expects($this->once())->method('getRequestUri')->will($this->returnValue($requestUri));
		$environment->_set('SAPIName', 'apache');
		$environment->_set('SERVER', array('SCRIPT_NAME' => $SCRIPT_NAME));

		$this->assertEquals((string)$expectedBaseUri, (string)$environment->getBaseUri());
	}

	/**
	 * @test
	 * @dataProvider httpAcceptStringsAndCorrespondingFormats
	 */
	public function getAcceptedFormatsReturnsListOfAcceptedFormatsAccordingToHTTPHeader($httpAcceptString, $expectedFormats) {
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('getHTTPAccept'), array('Testing'));
		$environment->expects($this->once())->method('getHTTPAccept')->will($this->returnValue($httpAcceptString));

		$this->assertEquals($expectedFormats, $environment->getAcceptedFormats());
	}

	/**
	 * Data provider for accepted format detection
	 *
	 * @return array
	 */
	public function httpAcceptStringsAndCorrespondingFormats() {
		return array(
			array('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', array('html', 'xml')),
			array('application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5', array('xml', 'html', 'png', 'txt')),
			array('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8,application/json', array('html', 'json', 'xml')),
			array('image/jpeg, application/x-ms-application, image/gif, application/xaml+xml, image/pjpeg, application/x-ms-xbap, application/x-shockwave-flash, application/msword, */*', array('gif', 'jpg')),
		);
	}

	/**
	 * @test
	 */
	public function getUploadedFilesJustReturnsThePreviouslyUntangledFILESVariable() {
		$_FILES = array('foo' => 'bar');
		$environment = new \TYPO3\FLOW3\Utility\Environment('Testing');
		$this->assertEquals(array('foo' => 'bar'), $environment->getUploadedFiles());
	}

	/**
	 * @test
	 */
	public function getRawServerEnvironmentJustReturnsTheSERVERVariable() {
		$_SERVER = array('foo' => 'bar', 'REQUEST_TIME' => $_SERVER['REQUEST_TIME']);
		$environment = new \TYPO3\FLOW3\Utility\Environment('Testing');
		$this->assertEquals($_SERVER, $environment->getRawServerEnvironment());
	}

	/**
	 * @test
	 */
	public function getSAPINameReturnsNotNullOnFreshlyConstructedEnvironment() {
		$environment = new \TYPO3\FLOW3\Utility\Environment('Testing');
		$this->assertNotNull($environment->getSAPIName());
	}

	/**
	 * @test
	 */
	public function getMaximumPathLengthReturnsCorrectValue() {
		$environment = new \TYPO3\FLOW3\Utility\Environment('Testing');
		$expectedValue = PHP_MAXPATHLEN;
		if ((integer)$expectedValue <= 0) {
			$this->fail('The PHP Constant PHP_MAXPATHLEN is not available on your system! Please file a PHP bug report.');
		}
		$this->assertEquals($expectedValue, $environment->getMaximumPathLength());
	}

	/**
	 * @test
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

		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array(), '', FALSE);
		$result = $environment->_call('untangleFilesArray', $convolutedFiles);

		$this->assertSame($untangledFiles, $result);
	}

	/**
	 * @test
	 */
	public function getRequestHeadersConvertsHTTPServerVariables() {
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array(), '', FALSE);
		$serverGlobal = array(
			'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
			'HTTP_CUSTOM_HEADER' => 'abcdefg'
		);
		$environment->_set('SERVER', $serverGlobal);

		$headers = $environment->getRequestHeaders();
		$this->assertEquals(array(
			'Accept-Encoding' => 'gzip,deflate',
			'Custom-Header' => 'abcdefg'
		), $headers);
	}

	/**
	 * @test
	 */
	public function getRequestHeadersRespectsAuthorizationVariables() {
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array(), '', FALSE);
		$serverGlobal = array(
			'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
			'HTTP_CUSTOM_HEADER' => 'abcdefg',
			'PHP_AUTH_USER' => 'admin',
			'PHP_AUTH_PW' => 'password'
		);
		$environment->_set('SERVER', $serverGlobal);

		$headers = $environment->getRequestHeaders();
		$this->assertEquals(array(
			'Accept-Encoding' => 'gzip,deflate',
			'Custom-Header' => 'abcdefg',
			'User' => 'admin',
			'Pw' => 'password'
		), $headers);
	}

	/**
	 * @test
	 */
	public function getRequestHeadersRespectsAuthorizationVariablesRedirectedWhenRunningPhpAsCgi() {
		$environment = $this->getAccessibleMock('TYPO3\FLOW3\Utility\Environment', array('dummy'), array(), '', FALSE);
		$serverGlobal = array(
			'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
			'HTTP_CUSTOM_HEADER' => 'abcdefg',
			'REDIRECT_REMOTE_AUTHORIZATION' => base64_encode('admin:password')
		);
		$environment->_set('SERVER', $serverGlobal);

		$headers = $environment->getRequestHeaders();
		$this->assertEquals(array(
			'Accept-Encoding' => 'gzip,deflate',
			'Custom-Header' => 'abcdefg',
			'User' => 'admin',
			'Pw' => 'password'
		), $headers);
	}
}
?>