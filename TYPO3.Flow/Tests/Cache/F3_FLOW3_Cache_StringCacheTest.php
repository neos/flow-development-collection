<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Cache;

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
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the String Cache
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class StringCacheTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function savePassesStringToBackend() {
		$theString = 'Just some value';
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('save')->with($this->equalTo('StringCacheTest'), $this->equalTo($theString));

		$cache = new F3::FLOW3::Cache::StringCache('StringCache', $backend);
		$cache->save('StringCacheTest', $theString);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException F3::FLOW3::Cache::Exception::InvalidData
	 */
	public function saveThrowsInvalidDataExceptionOnNonStringValues() {
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$cache = new F3::FLOW3::Cache::StringCache('StringCache', $backend);
		$cache->save('StringCacheTest', array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function loadLoadsStringValueFromBackend() {
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('load')->will($this->returnValue('Just some value'));

		$cache = new F3::FLOW3::Cache::StringCache('StringCache', $backend);
		$this->assertEquals('Just some value', $cache->load('StringCacheTest'), 'The returned value was not the expected string.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsResultFromBackend() {
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('has')->with($this->equalTo('StringCacheTest'))->will($this->returnValue(TRUE));

		$cache = new F3::FLOW3::Cache::StringCache('StringCache', $backend);
		$this->assertTRUE($cache->has('StringCacheTest'), 'has() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function removeCallsBackend() {
		$cacheIdentifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findEntriesByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(TRUE));

		$cache = new F3::FLOW3::Cache::StringCache('StringCache', $backend);
		$this->assertTRUE($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
	}
}
?>