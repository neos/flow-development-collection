<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Cache;

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

require_once ('Backend/MockBackend.php');

/**
 * Testcase for the Cache Factory
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CacheFactoryTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createReturnsInstanceOfTheSpecifiedCacheFrontend() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockCacheManager = $this->getMock('F3\FLOW3\Cache\CacheManager', array('registerCache'), array(), '', FALSE);

		$factory = new \F3\FLOW3\Cache\CacheFactory('Testing', $mockCacheManager, $mockEnvironment);

		$cache = $factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3\FLOW3\Cache\Frontend\VariableFrontend', 'F3\FLOW3\Cache\Backend\NullBackend');
		$this->assertInstanceOf('F3\FLOW3\Cache\Frontend\VariableFrontend', $cache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockCacheManager = $this->getMock('F3\FLOW3\Cache\CacheManager', array('registerCache'), array(), '', FALSE);

		$factory = new \F3\FLOW3\Cache\CacheFactory('Testing', $mockCacheManager, $mockEnvironment);

		$cache = $factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3\FLOW3\Cache\Frontend\VariableFrontend', 'F3\FLOW3\Cache\Backend\FileBackend');
		$this->assertType('F3\FLOW3\Cache\Backend\FileBackend', $cache->getBackend());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createRegistersTheCacheAtTheCacheManager() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);

		$mockCacheManager = $this->getMock('F3\FLOW3\Cache\CacheManager', array('registerCache'), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('registerCache');

		$factory = new \F3\FLOW3\Cache\CacheFactory('Testing', $mockCacheManager, $mockEnvironment);

		$factory->create('F3_FLOW3_Cache_FactoryTest_Cache', 'F3\FLOW3\Cache\Frontend\VariableFrontend', 'F3\FLOW3\Cache\Backend\FileBackend');
	}
}
?>