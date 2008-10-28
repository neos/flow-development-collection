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
 * Testcase for the Variable Cache
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class VariableCacheTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function savePassesSerializedStringToBackend() {
		$theString = 'Just some value';
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('save')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$cache->save('VariableCacheTest', $theString);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function savePassesSerializedArrayToBackend() {
		$theArray = array('Just some value', 'and another one.');
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('save')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theArray)));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$cache->save('VariableCacheTest', $theArray);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadLoadsStringValueFromBackend() {
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('load')->will($this->returnValue(serialize('Just some value')));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$this->assertEquals('Just some value', $cache->load('VariableCacheTest'), 'The returned value was not the expected string.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadLoadsArrayValueFromBackend() {
		$theArray = array('Just some value', 'and another one.');
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('load')->will($this->returnValue(serialize($theArray)));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$this->assertEquals($theArray, $cache->load('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadLoadsFalseBooleanValueFromBackend() {
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('load')->will($this->returnValue(serialize(FALSE)));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$this->assertFalse($cache->load('VariableCacheTest'), 'The returned value was not the FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsResultFromBackend() {
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);
		$backend->expects($this->once())->method('has')->with($this->equalTo('VariableCacheTest'))->will($this->returnValue(TRUE));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$this->assertTrue($cache->has('VariableCacheTest'), 'has() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function removeCallsBackend() {
		$cacheIdentifier = 'someCacheIdentifier';
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(TRUE));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findByTagCallsBackend() {
		$tag = 'sometag';
		$identifiers = array('one', 'two');
		$entries = array('one value', 'two value');
		$backend = $this->getMock('F3::FLOW3::Cache::AbstractBackend', array('load', 'save', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
		$backend->expects($this->exactly(2))->method('load')->will($this->onConsecutiveCalls(serialize('one value'), serialize('two value')));

		$cache = new F3::FLOW3::Cache::VariableCache('VariableCache', $backend);
		$this->assertEquals($entries, $cache->findByTag($tag), 'Did not receive the expected entries');
	}
}
?>