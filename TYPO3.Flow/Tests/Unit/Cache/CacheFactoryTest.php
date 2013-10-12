<?php
namespace TYPO3\Flow\Tests\Unit\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once ('Backend/MockBackend.php');
use TYPO3\Flow\Core\ApplicationContext;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the Cache Factory
 *
 */
class CacheFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $mockEnvironment;

	/**
	 * Creates the mocked filesystem used in the tests
	 */
	public function setUp() {
		vfsStream::setup('Foo');

		$this->mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', FALSE);
		$this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
		$this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfTheSpecifiedCacheFrontend() {
		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array('registerCache'), array(), '', FALSE);

		$factory = new \TYPO3\Flow\Cache\CacheFactory(new ApplicationContext('Testing'), $mockCacheManager, $this->mockEnvironment);

		$cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', 'TYPO3\Flow\Cache\Frontend\VariableFrontend', 'TYPO3\Flow\Cache\Backend\NullBackend');
		$this->assertInstanceOf('TYPO3\Flow\Cache\Frontend\VariableFrontend', $cache);
	}

	/**
	 * @test
	 */
	public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend() {
		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array('registerCache'), array(), '', FALSE);

		$factory = new \TYPO3\Flow\Cache\CacheFactory(new ApplicationContext('Testing'), $mockCacheManager, $this->mockEnvironment);

		$cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', 'TYPO3\Flow\Cache\Frontend\VariableFrontend', 'TYPO3\Flow\Cache\Backend\FileBackend');
		$this->assertInstanceOf('TYPO3\Flow\Cache\Backend\FileBackend', $cache->getBackend());
	}

	/**
	 * @test
	 */
	public function createRegistersTheCacheAtTheCacheManager() {
		$mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array('registerCache'), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('registerCache');

		$factory = new \TYPO3\Flow\Cache\CacheFactory(new ApplicationContext('Testing'), $mockCacheManager, $this->mockEnvironment);

		$factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', 'TYPO3\Flow\Cache\Frontend\VariableFrontend', 'TYPO3\Flow\Cache\Backend\FileBackend');
	}
}
