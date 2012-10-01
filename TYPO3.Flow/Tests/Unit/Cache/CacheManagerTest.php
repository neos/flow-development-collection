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

/**
 * Testcase for the Cache Manager
 *
 */
class CacheManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception\DuplicateIdentifierException
	 */
	public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier() {
		$manager = new \TYPO3\Flow\Cache\CacheManager();

		$cache1 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$cache2 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);
	}

	/**
	 * @test
	 */
	public function managerReturnsThePreviouslyRegisteredCached() {
		$manager = new \TYPO3\Flow\Cache\CacheManager();

		$cache1 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));

		$cache2 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));

		$manager->registerCache($cache1);
		$manager->registerCache($cache2);

		$this->assertSame($cache2, $manager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception\NoSuchCacheException
	 */
	public function getCacheThrowsExceptionForNonExistingIdentifier() {
		$manager = new \TYPO3\Flow\Cache\CacheManager();
		$cache = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));

		$manager->registerCache($cache);
		$manager->getCache('someidentifier');

		$manager->getCache('doesnotexist');
	}

	/**
	 * @test
	 */
	public function hasCacheReturnsCorrectResult() {
		$manager = new \TYPO3\Flow\Cache\CacheManager();
		$cache1 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$manager->registerCache($cache1);

		$this->assertTrue($manager->hasCache('cache1'), 'hasCache() did not return TRUE.');
		$this->assertFalse($manager->hasCache('cache2'), 'hasCache() did not return FALSE.');
	}

	/**
	 * @test
	 */
	public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches() {
		$manager = new \TYPO3\Flow\Cache\CacheManager();

		$cache1 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache1);

		$cache2 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$manager->registerCache($cache2);

		$manager->flushCachesByTag('theTag');
	}

	/**
	 * @test
	 */
	public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches() {
		$manager = new \TYPO3\Flow\Cache\CacheManager();

		$cache1 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flush');
		$manager->registerCache($cache1);

		$cache2 = $this->getMock('TYPO3\Flow\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag'), array(), '', FALSE);
		$cache2->expects($this->once())->method('flush');
		$manager->registerCache($cache2);

		$manager->flushCaches();
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesWithChangedClassFileRemovesCacheEntryFromObjectClassesCache() {
		$objectClassCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\FrontendInterface');
		$objectClassCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('FLOW3_Object_Classes'));
		$objectConfigurationCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\FrontendInterface');
		$objectConfigurationCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('FLOW3_Object_Configuration'));
		$reflectionStatusCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\FrontendInterface');
		$reflectionStatusCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('FLOW3_Reflection_Status'));

		$manager = new \TYPO3\FLOW3\Cache\CacheManager();
		$manager->registerCache($objectClassCache);
		$manager->registerCache($objectConfigurationCache);
		$manager->registerCache($reflectionStatusCache);

		$objectClassCache->expects($this->once())->method('remove')->with('TYPO3_FLOW3_Cache_CacheManager');
		$objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

		$manager->flushSystemCachesByChangedFiles('FLOW3_ClassFiles', array(
			FLOW3_PATH_PACKAGES . '/Framework/TYPO3.FLOW3/Classes/TYPO3/FLOW3/Cache/CacheManager.php' => \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesWithChangedTestFileRemovesCacheEntryFromObjectClassesCache() {
		$objectClassCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\FrontendInterface');
		$objectClassCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('FLOW3_Object_Classes'));
		$objectConfigurationCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\FrontendInterface');
		$objectConfigurationCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('FLOW3_Object_Configuration'));
		$reflectionStatusCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\FrontendInterface');
		$reflectionStatusCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('FLOW3_Reflection_Status'));

		$manager = new \TYPO3\FLOW3\Cache\CacheManager();
		$manager->registerCache($objectClassCache);
		$manager->registerCache($objectConfigurationCache);
		$manager->registerCache($reflectionStatusCache);

		$objectClassCache->expects($this->once())->method('remove')->with('TYPO3_FLOW3_Tests_Functional_Cache_CacheManagerTest');
		$objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

		$manager->flushSystemCachesByChangedFiles('FLOW3_ClassFiles', array(
			FLOW3_PATH_PACKAGES . '/Framework/TYPO3.FLOW3/Tests/Functional/Cache/CacheManagerTest.php' => \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

}
?>