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

use TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;

/**
 * Testcase for the Cache Manager
 *
 */
class CacheManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $mockSystemLogger;

	public function setUp() {
		$this->cacheManager = new \TYPO3\Flow\Cache\CacheManager();

		$this->mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$this->cacheManager->injectSystemLogger($this->mockSystemLogger);
	}

	/**
	 * Creates a mock cache with the given $cacheIdentifier and registers it with the cache manager
	 *
	 * @param $cacheIdentifier
	 * @return \TYPO3\Flow\Cache\Frontend\FrontendInterface
	 */
	protected function registerCache($cacheIdentifier) {
		$cache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));
		$this->cacheManager->registerCache($cache);

		return $cache;
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception\DuplicateIdentifierException
	 */
	public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier() {
		$cache1 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$cache2 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$this->cacheManager->registerCache($cache1);
		$this->cacheManager->registerCache($cache2);
	}

	/**
	 * @test
	 */
	public function managerReturnsThePreviouslyRegisteredCached() {
		$cache1 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));

		$cache2 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache2->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache2'));

		$this->cacheManager->registerCache($cache1);
		$this->cacheManager->registerCache($cache2);

		$this->assertSame($cache2, $this->cacheManager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception\NoSuchCacheException
	 */
	public function getCacheThrowsExceptionForNonExistingIdentifier() {
		$cache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('someidentifier'));

		$this->cacheManager->registerCache($cache);
		$this->cacheManager->getCache('someidentifier');

		$this->cacheManager->getCache('doesnotexist');
	}

	/**
	 * @test
	 */
	public function hasCacheReturnsCorrectResult() {
		$cache1 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$this->cacheManager->registerCache($cache1);

		$this->assertTrue($this->cacheManager->hasCache('cache1'), 'hasCache() did not return TRUE.');
		$this->assertFalse($this->cacheManager->hasCache('cache2'), 'hasCache() did not return FALSE.');
	}

	/**
	 * @test
	 */
	public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches() {
		$cache1 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$this->cacheManager->registerCache($cache1);

		$cache2 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache2->expects($this->once())->method('flushByTag')->with($this->equalTo('theTag'));
		$this->cacheManager->registerCache($cache2);

		$this->cacheManager->flushCachesByTag('theTag');
	}

	/**
	 * @test
	 */
	public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches() {
		$cache1 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache1->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('cache1'));
		$cache1->expects($this->once())->method('flush');
		$this->cacheManager->registerCache($cache1);

		$cache2 = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\AbstractFrontend')->disableOriginalConstructor()->getMock();
		$cache2->expects($this->once())->method('flush');
		$this->cacheManager->registerCache($cache2);

		$this->cacheManager->flushCaches();
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesWithChangedClassFileRemovesCacheEntryFromObjectClassesCache() {
		$objectClassCache = $this->registerCache('Flow_Object_Classes');
		$objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
		$this->registerCache('Flow_Reflection_Status');

		$objectClassCache->expects($this->once())->method('remove')->with('TYPO3_Flow_Cache_CacheManager');
		$objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', array(
			FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Classes/TYPO3/Flow/Cache/CacheManager.php' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesWithChangedTestFileRemovesCacheEntryFromObjectClassesCache() {
		$objectClassCache = $this->registerCache('Flow_Object_Classes');
		$objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
		$this->registerCache('Flow_Reflection_Status');

		$objectClassCache->expects($this->once())->method('remove')->with('TYPO3_Flow_Tests_Functional_Cache_CacheManagerTest');
		$objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', array(
			FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Functional/Cache/CacheManagerTest.php' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesDoesNotFlushPolicyCacheIfNoPolicyFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');
		$policyCache = $this->registerCache('Flow_Security_Policy');
		$policyCache->expects($this->never())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesFlushesPolicyAndDoctrineCachesIfAPolicyFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');

		$policyCache = $this->registerCache('Flow_Security_Authorization_Privilege_Method');
		$policyCache->expects($this->once())->method('flush');

		$doctrineCache = $this->registerCache('Flow_Persistence_Doctrine');
		$doctrineCache->expects($this->once())->method('flush');

		$doctrineResultsCache = $this->registerCache('Flow_Persistence_Doctrine_Results');
		$doctrineResultsCache->expects($this->once())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
			'Some/Package/Configuration/Policy.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesDoesNotFlushRoutingCacheIfNoRoutesFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');

		$matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
		$matchResultsCache->expects($this->never())->method('flush');
		$resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
		$resolveCache->expects($this->never())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesFlushesRoutingCacheIfARoutesFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');

		$matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
		$matchResultsCache->expects($this->once())->method('flush');
		$resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
		$resolveCache->expects($this->once())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
			'Some/Package/Configuration/Routes.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
			'A/Different/Package/Configuration/Routes.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesFlushesRoutingCacheIfACustomSubRoutesFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');

		$matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
		$matchResultsCache->expects($this->once())->method('flush');
		$resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
		$resolveCache->expects($this->once())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
			'Some/Package/Configuration/Routes.Custom.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesTriggersAopProxyClassRebuild() {
		$objectClassesCache = $this->registerCache('Flow_Object_Classes');
		$objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');

		$objectClassesCache->expects($this->once())->method('flush');
		$objectConfigurationCache->expects($this->at(0))->method('remove')->with('allAspectClassesUpToDate');
		$objectConfigurationCache->expects($this->at(1))->method('remove')->with('allCompiledCodeUpToDate');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', array());
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesDoesNotFlushI18nCacheIfNoTranslationFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');

		$i18nCache = $this->registerCache('Flow_I18n_XmlModelCache');
		$i18nCache->expects($this->never())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_TranslationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
		));
	}

	/**
	 * @test
	 */
	public function flushSystemCachesByChangedFilesFlushesI18nCacheIfATranslationFileHasBeenModified() {
		$this->registerCache('Flow_Object_Classes');
		$this->registerCache('Flow_Object_Configuration');

		$i18nCache = $this->registerCache('Flow_I18n_XmlModelCache');
		$i18nCache->expects($this->once())->method('flush');

		$this->cacheManager->flushSystemCachesByChangedFiles('Flow_TranslationFiles', array(
			'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
			'Some/Package/Resources/Private/Translations/en/Foo.xlf' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
		));
	}


}
