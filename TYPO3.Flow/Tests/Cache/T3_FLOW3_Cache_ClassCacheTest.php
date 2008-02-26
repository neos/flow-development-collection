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
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Class Cache
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Cache_ClassCacheTest extends T3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveThrowsExceptionOnNonExistantClass() {
		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend');
		$cache = new T3_FLOW3_Cache_ClassCache('ClassCache', $backend);
		try {
			$cache->save('ThisClassDoesntExist');
			$this->fail('save() did not throw an exception.');
		} catch(T3_FLOW3_Cache_Exception_InvalidClass $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function savePassesClassCodeToBackend() {
		$componentManagerReflection = new ReflectionClass('T3_FLOW3_Component_Manager');

		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array('save'));
		$backend->expects($this->once())->method('save')->with($this->equalTo((string)$componentManagerReflection));

		$cache = new T3_FLOW3_Cache_ClassCache('ClassCache', $backend);
		$cache->save('T3_FLOW3_Component_Manager');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadLoadsClassCodeFromBackendAndEvaluatesIt() {
		$className = uniqid('T3_FLOW3_Fixture_CachedClass');
		$this->assertFalse(class_exists($className), 'The class "' . $className . '" already existed!');

		$classCode = '
			class ' . $className . ' {}
		';

		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array('load'));
		$backend->expects($this->once())->method('load')->will($this->returnValue($classCode));

		$cache = new T3_FLOW3_Cache_ClassCache('ClassCache', $backend);
		$cache->load($className);

		$this->assertTrue(class_exists($className), 'The class doesn\'t exist after calling load()!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadThrowsExceptionOnTryingToLoadAClassTwice() {
		$className = uniqid('T3_FLOW3_Fixture_CachedClass');
		$this->assertFalse(class_exists($className), 'The class "' . $className . '" already existed!');

		$classCode = '
			class ' . $className . ' {}
		';

		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array('load'));
		$backend->expects($this->atLeastOnce())->method('load')->will($this->returnValue($classCode));

		$cache = new T3_FLOW3_Cache_ClassCache('ClassCache', $backend);
		$cache->load($className);
		try {
			$cache->load($className);
			$this->fail('load() did not throw an exception.');
		} catch (T3_FLOW3_Cache_Exception_ClassAlreadyLoaded $exception) {
		}
	}
}
?>