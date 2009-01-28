<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache;

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
 * Testcase for the Cache Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeCreatesAndRegistersAllCachesDefinedInTheCachesConfiguration() {
		$mockCacheFactory = $this->getMock('F3\FLOW3\Cache\Factory', array(), array(), '', FALSE);
		$mockCacheFactory->expects($this->at(1))->method('create')->with('Cache1', 'F3\FLOW3\Cache\Frontend\VariableFrontend', 'F3\FLOW3\Cache\Backend\FileBackend', array());
		$mockCacheFactory->expects($this->at(2))->method('create')->with('Cache2', 'F3\FLOW3\Cache\Frontend\StringFrontend', 'F3\FLOW3\Cache\Backend\NullBackend', array('foo' => 'bar'));

		$cacheConfigurations = array(
			'Cache1' => array(),
			'Cache2' => array(
				'frontend' => 'F3\FLOW3\Cache\Frontend\StringFrontend',
				'backend' => 'F3\FLOW3\Cache\Backend\NullBackend',
				'backendOptions' => array('foo' => 'bar')
			),
		);

		$manager = new \F3\FLOW3\Cache\Manager();
		$manager->setCacheConfigurations($cacheConfigurations);
		$manager->injectCacheFactory($mockCacheFactory);
		$manager->initialize();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\DuplicateIdentifier
	 */
	public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier() {
		$manager = new \F3\FLOW3\Cache\Manager();
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$cache2 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function managerReturnsThePreviouslyRegisteredCached() {
		$manager = new \F3\FLOW3\Cache\Manager();
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));

		$cache2 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);

		$this->assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\NoSuchCache
	 */
	public function getCacheThrowsExceptionForNonExistingIdentifier() {
		$manager = new \F3\FLOW3\Cache\Manager();
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));

		$manager->registerCache($cache);
		$manager->getCache('someidentifier');

		$manager->getCache('doesnotexist');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasCacheReturnsCorrectResult() {
		$manager = new \F3\FLOW3\Cache\Manager();
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);
		$cache1 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$manager->registerCache($cache1);

		$this->assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
		$this->assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches() {
		$manager = new \F3\FLOW3\Cache\Manager();
		$manager->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache1);

		$cache2 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache2);

		$manager->flushCachesByTag('theTag');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches() {
		$manager = new \F3\FLOW3\Cache\Manager();
		$manager->injectSystemLogger($this->getMock('F3\FLOW3\Log\SystemLoggerInterface'));
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\AbstractBackend', array(), array(), '', FALSE);

		$cache1 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flush');
		$manager->registerCache($cache1);

		$cache2 = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flush');
		$manager->registerCache($cache2);

		$manager->flushCaches();
	}
}
?>