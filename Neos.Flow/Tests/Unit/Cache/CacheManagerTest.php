<?php
namespace Neos\Flow\Tests\Unit\Cache;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Cache;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Environment;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the Cache Manager
 */
class CacheManagerTest extends UnitTestCase
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var ConfigurationManager
     */
    protected $mockConfigurationManager;

    /**
     * @var LoggerInterface
     */
    protected $mockSystemLogger;

    /**
     * @var Environment
     */
    protected $mockEnvironment;

    protected function setUp(): void
    {
        vfsStream::setup('Foo');
        $this->cacheManager = new CacheManager();

        $this->mockEnvironment = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $this->mockEnvironment->expects(self::any())->method('getPathToTemporaryDirectory')->will(self::returnValue('vfs://Foo/'));
        $this->cacheManager->injectEnvironment($this->mockEnvironment);

        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->cacheManager->injectLogger($this->mockSystemLogger);
        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->cacheManager->injectConfigurationManager($this->mockConfigurationManager);
    }

    /**
     * Creates a mock cache with the given $cacheIdentifier and registers it with the cache manager
     *
     * @param $cacheIdentifier
     * @return Cache\Frontend\FrontendInterface
     */
    protected function registerCache($cacheIdentifier)
    {
        $cache = $this->createMock(Cache\Frontend\FrontendInterface::class);
        $cache->expects(self::any())->method('getIdentifier')->will(self::returnValue($cacheIdentifier));
        $this->cacheManager->registerCache($cache);

        return $cache;
    }

    /**
     * @test
     */
    public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier()
    {
        $this->expectException(Cache\Exception\DuplicateIdentifierException::class);
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('test'));

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('test'));

        $this->cacheManager->registerCache($cache1);
        $this->cacheManager->registerCache($cache2);
    }

    /**
     * @test
     */
    public function managerReturnsThePreviouslyRegisteredCached()
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache1'));

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache2'));

        $this->cacheManager->registerCache($cache1);
        $this->cacheManager->registerCache($cache2);

        self::assertSame($cache2, $this->cacheManager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');
    }

    /**
     * @test
     */
    public function getCacheThrowsExceptionForNonExistingIdentifier()
    {
        $this->expectException(Cache\Exception\NoSuchCacheException::class);
        $cache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getCache('someidentifier');

        $this->cacheManager->getCache('doesnotexist');
    }

    /**
     * @test
     */
    public function hasCacheReturnsCorrectResult()
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache1'));
        $this->cacheManager->registerCache($cache1);

        self::assertTrue($this->cacheManager->hasCache('cache1'), 'hasCache() did not return true.');
        self::assertFalse($this->cacheManager->hasCache('cache2'), 'hasCache() did not return false.');
    }

    /**
     * @test
     */
    public function isCachePersistentReturnsCorrectResult()
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache1'));
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache2'));
        $this->cacheManager->registerCache($cache2, true);

        self::assertFalse($this->cacheManager->isCachePersistent('cache1'));
        self::assertTrue($this->cacheManager->isCachePersistent('cache2'));
    }

    /**
     * @test
     */
    public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches()
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache1'));
        $cache1->expects(self::once())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache2'));
        $cache2->expects(self::once())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($cache2);

        $persistentCache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $persistentCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('persistentCache'));
        $persistentCache->expects(self::never())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($persistentCache, true);

        $this->cacheManager->flushCachesByTag('theTag');
    }

    /**
     * @test
     */
    public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches()
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache1'));
        $cache1->expects(self::once())->method('flush');
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('cache2'));
        $cache2->expects(self::once())->method('flush');
        $this->cacheManager->registerCache($cache2);

        $persistentCache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $persistentCache->expects(self::atLeastOnce())->method('getIdentifier')->will(self::returnValue('persistentCache'));
        $persistentCache->expects(self::never())->method('flush');
        $this->cacheManager->registerCache($persistentCache, true);

        $this->cacheManager->flushCaches();
    }

    /**
     * @test
     */
    public function flushCachesCallsTheFlushConfigurationCacheMethodOfConfigurationManager()
    {
        $this->mockConfigurationManager->expects(self::once())->method('flushConfigurationCache');

        $this->cacheManager->flushCaches();
    }

    /**
     * @test
     */
    public function flushCachesDeletesAvailableProxyClassesFile()
    {
        file_put_contents('vfs://Foo/AvailableProxyClasses.php', '// dummy');
        $this->cacheManager->flushCaches();
        self::assertFileDoesNotExist('vfs://Foo/AvailableProxyClasses.php');
    }

    /**
     * @test
     */
    public function flushConfigurationCachesByChangedFilesFlushesConfigurationCache()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $this->mockConfigurationManager->expects(self::once())->method('refreshConfiguration');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', []);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesWithChangedClassFileRemovesCacheEntryFromObjectClassesCache()
    {
        $objectClassCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
        $this->registerCache('Flow_Reflection_Status');

        $objectClassCache->expects(self::once())->method('remove')->with('Neos_Flow_Cache_CacheManager');
        $objectConfigurationCache->expects(self::once())->method('remove')->with('allCompiledCodeUpToDate');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', [
            FLOW_PATH_PACKAGES . 'Framework/Neos.Flow/Classes/Cache/CacheManager.php' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesWithChangedTestFileRemovesCacheEntryFromObjectClassesCache()
    {
        $objectClassCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
        $this->registerCache('Flow_Reflection_Status');

        $objectClassCache->expects(self::once())->method('remove')->with('Neos_Flow_Tests_Unit_Cache_CacheManagerTest');
        $objectConfigurationCache->expects(self::once())->method('remove')->with('allCompiledCodeUpToDate');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', [
            __FILE__ => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesDoesNotFlushPolicyCacheIfNoPolicyFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');
        $policyCache = $this->registerCache('Flow_Security_Policy');
        $policyCache->expects(self::never())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesPolicyAndDoctrineCachesIfAPolicyFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $policyCache = $this->registerCache('Flow_Security_Authorization_Privilege_Method');
        $policyCache->expects(self::once())->method('flush');

        $aopExpressionCache = $this->registerCache('Flow_Aop_RuntimeExpressions');
        $aopExpressionCache->expects(self::once())->method('flush');

        $doctrineCache = $this->registerCache('Flow_Persistence_Doctrine');
        $doctrineCache->expects(self::once())->method('flush');

        $doctrineResultsCache = $this->registerCache('Flow_Persistence_Doctrine_Results');
        $doctrineResultsCache->expects(self::once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Configuration/Policy.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesDoesNotFlushRoutingCacheIfNoRoutesFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
        $matchResultsCache->expects(self::never())->method('flush');
        $resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
        $resolveCache->expects(self::never())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesRoutingCacheIfARoutesFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
        $matchResultsCache->expects(self::once())->method('flush');
        $resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
        $resolveCache->expects(self::once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Configuration/Routes.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'A/Different/Package/Configuration/Routes.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesRoutingCacheIfACustomSubRoutesFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
        $matchResultsCache->expects(self::once())->method('flush');
        $resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
        $resolveCache->expects(self::once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Configuration/Routes.Custom.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
        ]);
    }

    /**
     * @return array
     */
    public function configurationFileChangesNeedAopProxyClassesRebuild()
    {
        return [
            ['A/Different/Package/Configuration/Routes.yaml', false],
            ['A/Different/Package/Configuration/Views.yaml', false],
            ['A/Different/Package/Configuration/Objects.yaml', true],
            ['A/Different/Package/Configuration/Policy.yaml', true],
            ['A/Different/Package/Configuration/Settings.yaml', true],
            ['A/Different/Package/Configuration/Settings.Custom.yaml', true],
        ];
    }

    /**
     * @test
     * @dataProvider configurationFileChangesNeedAopProxyClassesRebuild
     */
    public function flushSystemCachesByChangedFilesTriggersAopProxyClassRebuildIfNeeded($changedFile, $needsAopProxyClassRebuild)
    {
        $this->registerCache('Flow_Security_Authorization_Privilege_Method');
        $this->registerCache('Flow_Mvc_Routing_Route');
        $this->registerCache('Flow_Mvc_ViewConfigurations');
        $this->registerCache('Flow_Persistence_Doctrine');
        $this->registerCache('Flow_Persistence_Doctrine_Results');
        $this->registerCache('Flow_Mvc_Routing_Resolve');
        $this->registerCache('Flow_Aop_RuntimeExpressions');

        $objectClassesCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');

        if ($needsAopProxyClassRebuild) {
            $objectClassesCache->expects(self::once())->method('flush');
            $objectConfigurationCache->method('remove')->withConsecutive(['allAspectClassesUpToDate'], ['allCompiledCodeUpToDate']);
        } else {
            $objectClassesCache->expects(self::never())->method('flush');
            $objectConfigurationCache->expects(self::never())->method('remove')->with('allAspectClassesUpToDate');
            $objectConfigurationCache->expects(self::never())->method('remove')->with('allCompiledCodeUpToDate');
        }

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            $changedFile => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesDoesNotFlushI18nCacheIfNoTranslationFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $i18nCache = $this->registerCache('Flow_I18n_XmlModelCache');
        $i18nCache->expects(self::never())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_TranslationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesI18nCacheIfATranslationFileHasBeenModified()
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $i18nCache = $this->registerCache('Flow_I18n_XmlModelCache');
        $i18nCache->expects(self::once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_TranslationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Resources/Private/Translations/en/Foo.xlf' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
        ]);
    }
}
