<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache;

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
 * @version $Id:\F3\FLOW3\AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the String Cache
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class StringCacheTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPassesStringToBackend() {
		$theString = 'Just some value';
		$backend = $this->getMock('F3\FLOW3\Cache\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('set')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString));

		$cache = new \F3\FLOW3\Cache\StringCache('StringCache', $backend);
		$cache->set('StringCacheTest', $theString);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\InvalidData
	 */
	public function setThrowsInvalidDataExceptionOnNonStringValues() {
		$backend = $this->getMock('F3\FLOW3\Cache\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$cache = new \F3\FLOW3\Cache\StringCache('StringCache', $backend);
		$cache->set('StringCacheTest', array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getFetchesStringValueFromBackend() {
		$backend = $this->getMock('F3\FLOW3\Cache\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('get')->will($this->returnValue('Just some value'));

		$cache = new \F3\FLOW3\Cache\StringCache('StringCache', $backend);
		$this->assertEquals('Just some value', $cache->get('StringCacheTest'), 'The returned value was not the expected string.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsResultFromBackend() {
		$backend = $this->getMock('F3\FLOW3\Cache\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('has')->with($this->equalTo('StringCacheTest'))->will($this->returnValue(TRUE));

		$cache = new \F3\FLOW3\Cache\StringCache('StringCache', $backend);
		$this->assertTrue($cache->has('StringCacheTest'), 'has() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function removeCallsBackend() {
		$cacheIdentifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3\FLOW3\Cache\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(TRUE));

		$cache = new \F3\FLOW3\Cache\StringCache('StringCache', $backend);
		$this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getByTagCallsBackend() {
		$tag = 'sometag';
		$identifiers = array('one', 'two');
		$entries = array('one value', 'two value');
		$backend = $this->getMock('F3\FLOW3\Cache\AbstractBackend', array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
		$backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls('one value', 'two value'));

		$cache = new \F3\FLOW3\Cache\StringCache('StringCache', $backend);
		$this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
	}
}
?>