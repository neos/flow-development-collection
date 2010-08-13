<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Frontend;

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
 * Testcase for the PHP source code cache frontend
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PhpFrontendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setChecksIfTheIdentifierIsValid() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\StringFrontend', array('isValidEntryIdentifier'), array(), '', FALSE);
		$cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(FALSE));
		$cache->set('foo', 'bar');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPassesPhpSourceCodeTagsAndLifetimeToBackend() {
		$originalSourceCode = 'return "hello world!";';
		$modifiedSourceCode = '<?php' . chr(10) . $originalSourceCode . chr(10) . '__halt_compiler();';

		$mockBackend = $this->getMock('F3\FLOW3\Cache\Backend\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, array('tags'), 1234);

		$cache = $this->getAccessibleMock('F3\FLOW3\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->_set('backend', $mockBackend);
		$cache->set('Foo-Bar', $originalSourceCode, array('tags'), 1234);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\InvalidDataException
	 */
	public function setThrowsInvalidDataExceptionOnNonStringValues() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->set('Foo-Bar', array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function requireOnceCallsTheBackendsRequireOnceMethod() {
		$mockBackend = $this->getMock('F3\FLOW3\Cache\Backend\PhpCapableBackendInterface', array(), array(), '', FALSE);
		$mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->will($this->returnValue('hello world!'));

		$cache = $this->getAccessibleMock('F3\FLOW3\Cache\Frontend\PhpFrontend', array('dummy'), array(), '', FALSE);
		$cache->_set('backend', $mockBackend);

		$result = $cache->requireOnce('Foo-Bar');
		$this->assertSame('hello world!', $result);
	}
}
?>