<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Frontend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the PHP source code cache frontend
 *
 */
class PhpFrontendTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 */
	public function setChecksIfTheIdentifierIsValid() {
		$cache = $this->getMock('TYPO3\Flow\Cache\Frontend\StringFrontend', array('isValidEntryIdentifier'), array(), '', FALSE);
		$cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(FALSE));
		$cache->set('foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setPassesPhpSourceCodeTagsAndLifetimeToBackend() {
		$originalSourceCode = 'return "hello world!";';
		$modifiedSourceCode = '<?php' . chr(10) . $originalSourceCode . chr(10) . '#';

		$mockBackend = $this->getMock('TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, array('tags'), 1234);

		$cache = $this->getAccessibleMock('TYPO3\Flow\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->_set('backend', $mockBackend);
		$cache->set('Foo-Bar', $originalSourceCode, array('tags'), 1234);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
	 */
	public function setThrowsInvalidDataExceptionOnNonStringValues() {
		$cache = $this->getMock('TYPO3\Flow\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->set('Foo-Bar', array());
	}

	/**
	 * @test
	 */
	public function requireOnceCallsTheBackendsRequireOnceMethod() {
		$mockBackend = $this->getMock('TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));

		$cache = $this->getAccessibleMock('TYPO3\Flow\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->_set('backend', $mockBackend);

		$result = $cache->requireOnce('Foo-Bar');
		$this->assertSame('hello world!', $result);
	}
}
?>