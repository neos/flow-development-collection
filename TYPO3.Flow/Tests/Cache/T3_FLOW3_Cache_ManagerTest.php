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
 * Testcase for the Cache Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Cache_CacheManagerTest extends T3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier() {
		$manager = new T3_FLOW3_Cache_Manager();
		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$cache2 = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$manager->registerCache($cache1);
		try {
			$manager->registerCache($cache2);
			$this->fail('The cache manager did not throw an exception.');
		} catch (T3_FLOW3_Cache_Exception_DuplicateIdentifier $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function managerReturnsThePreviouslyRegisteredCached() {
		$manager = new T3_FLOW3_Cache_Manager();
		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));

		$cache2 = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);

		$this->assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheThrowsExceptionForNonExistingIdentifier() {
		$manager = new T3_FLOW3_Cache_Manager();
		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));

		$manager->registerCache($cache);
		$manager->getCache('someidentifier');
		try {
			$manager->getCache('doesnotexist');
			$this->fail('The cache manager did not throw an exception.');
		} catch (T3_FLOW3_Cache_Exception_NoSuchCache $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasCacheReturnsCorrectResult() {
		$manager = new T3_FLOW3_Cache_Manager();
		$backend = $this->getMock('T3_FLOW3_Cache_AbstractBackend', array(), array(), '', FALSE);
		$cache1 = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$manager->registerCache($cache1);

		$this->assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
		$this->assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
	}
}
?>