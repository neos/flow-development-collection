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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the string cache frontend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringFrontendTest extends \F3\Testing\BaseTestCase {

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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPassesStringToBackend() {
		$theString = 'Just some value';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString));

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$cache->set('StringCacheTest', $theString);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\InvalidData
	 */
	public function setThrowsInvalidDataExceptionOnNonStringValues() {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$cache->set('StringCacheTest', array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getFetchesStringValueFromBackend() {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('get')->will($this->returnValue('Just some value'));

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$this->assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsResultFromBackend() {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('has')->with($this->equalTo('StringCacheTest'))->will($this->returnValue(TRUE));

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$this->assertTrue($cache->has('StringCacheTest'), 'has() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function removeCallsBackend() {
		$cacheIdentifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(TRUE));

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getByTagRejectsInvalidTags() {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\BackendInterface', array(), array(), '', FALSE);
		$backend->expects($this->never())->method('getByTag');

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$cache->getByTag('SomeInvalid\Tag');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getByTagCallsBackend() {
		$tag = 'sometag';
		$identifiers = array('one', 'two');
		$entries = array('one value', 'two value');
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
		$backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls('one value', 'two value'));

		$cache = new \F3\FLOW3\Cache\Frontend\StringFrontend('StringFrontend', $backend);
		$this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
	}
}
?>