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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

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
        $this->mockEnvironment->method('getPathToTemporaryDirectory')->willReturn(('vfs://Foo/'));
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
     * @return Cache\Frontend\FrontendInterface|MockObject
     */
    protected function registerCache($cacheIdentifier): Cache\Frontend\FrontendInterface
    {
        $cache = $this->createMock(Cache\Frontend\FrontendInterface::class);
        $cache->method('getIdentifier')->willReturn(($cacheIdentifier));
        $this->cacheManager->registerCache($cache);

        return $cache;
    }

    /**
     * @test
     */
    public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier(): void
    {
        $this->expectException(Cache\Exception\DuplicateIdentifierException::class);
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('test'));

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('test'));

        $this->cacheManager->registerCache($cache1);
        $this->cacheManager->registerCache($cache2);
    }

    /**
     * @test
     */
    public function managerReturnsThePreviouslyRegisteredCached(): void
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));

        $this->cacheManager->registerCache($cache1);
        $this->cacheManager->registerCache($cache2);

        self::assertSame($cache2, $this->cacheManager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');

        $cacheItemPool = $this->cacheManager->getCacheItemPool('cache2');
        self::assertInstanceOf(CacheItemPoolInterface::class, $cacheItemPool);

        $simpleCache = $this->cacheManager->getSimpleCache('cache2');
        self::assertInstanceOf(CacheInterface::class, $simpleCache);
    }

    /**
     * @test
     */
    public function getCacheThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(Cache\Exception\NoSuchCacheException::class);
        $cache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getCache('someidentifier');

        $this->cacheManager->getCache('doesnotexist');
    }

    /**
     * @test
     */
    public function getCacheItemPoolThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(Cache\Exception\NoSuchCacheException::class);
        $cache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getCacheItemPool('someidentifier');

        $this->cacheManager->getCacheItemPool('doesnotexist');
    }

    /**
     * @test
     */
    public function getSimpleCacheThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(Cache\Exception\NoSuchCacheException::class);
        $cache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getSimpleCache('someidentifier');

        $this->cacheManager->getSimpleCache('doesnotexist');
    }

    /**
     * @test
     */
    public function hasCacheReturnsCorrectResult(): void
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $this->cacheManager->registerCache($cache1);

        self::assertTrue($this->cacheManager->hasCache('cache1'), 'hasCache() did not return true.');
        self::assertFalse($this->cacheManager->hasCache('cache2'), 'hasCache() did not return false.');
    }

    /**
     * @test
     */
    public function isCachePersistentReturnsCorrectResult(): void
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));
        $this->cacheManager->registerCache($cache2, true);

        self::assertFalse($this->cacheManager->isCachePersistent('cache1'));
        self::assertTrue($this->cacheManager->isCachePersistent('cache2'));
    }

    /**
     * @test
     */
    public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches(): void
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $cache1->expects($this->once())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));
        $cache2->expects($this->once())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($cache2);

        $persistentCache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $persistentCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('persistentCache'));
        $persistentCache->expects($this->never())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($persistentCache, true);

        $this->cacheManager->flushCachesByTag('theTag');
    }

    /**
     * @test
     */
    public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches(): void
    {
        $cache1 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $cache1->expects($this->once())->method('flush');
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));
        $cache2->expects($this->once())->method('flush');
        $this->cacheManager->registerCache($cache2);

        $persistentCache = $this->getMockBuilder(Cache\Frontend\AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $persistentCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('persistentCache'));
        $persistentCache->expects($this->never())->method('flush');
        $this->cacheManager->registerCache($persistentCache, true);

        $this->cacheManager->flushCaches();
    }

    /**
     * @test
     */
    public function flushCachesCallsTheFlushConfigurationCacheMethodOfConfigurationManager(): void
    {
        $this->mockConfigurationManager->expects($this->once())->method('flushConfigurationCache');

        $this->cacheManager->flushCaches();
    }

    /**
     * @test
     */
    public function flushCachesDeletesAvailableProxyClassesFile(): void
    {
        file_put_contents('vfs://Foo/AvailableProxyClasses.php', '// dummy');
        $this->cacheManager->flushCaches();
        self::assertFileDoesNotExist('vfs://Foo/AvailableProxyClasses.php');
    }

    /**
     * @test
     */
    public function flushConfigurationCachesByChangedFilesFlushesConfigurationCache(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $this->mockConfigurationManager->expects($this->once())->method('refreshConfiguration');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', []);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesWithChangedClassFileRemovesCacheEntryFromObjectClassesCache(): void
    {
        $objectClassCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
        $this->registerCache('Flow_Reflection_Status');

        $objectClassCache->expects($this->once())->method('remove')->with('Neos_Flow_Cache_CacheManager');
        $objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', [
            FLOW_PATH_PACKAGES . 'Framework/Neos.Flow/Classes/Cache/CacheManager.php' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesWithChangedTestFileRemovesCacheEntryFromObjectClassesCache(): void
    {
        $objectClassCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
        $this->registerCache('Flow_Reflection_Status');

        $objectClassCache->expects($this->once())->method('remove')->with('Neos_Flow_Tests_Unit_Cache_CacheManagerTest');
        $objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', [
            __FILE__ => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesDoesNotFlushPolicyCacheIfNoPolicyFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');
        $policyCache = $this->registerCache('Flow_Security_Policy');
        $policyCache->expects($this->never())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesPolicyAndDoctrineCachesIfAPolicyFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $policyCache = $this->registerCache('Flow_Security_Authorization_Privilege_Method');
        $policyCache->expects($this->once())->method('flush');

        $aopExpressionCache = $this->registerCache('Flow_Aop_RuntimeExpressions');
        $aopExpressionCache->expects($this->once())->method('flush');

        $doctrineCache = $this->registerCache('Flow_Persistence_Doctrine');
        $doctrineCache->expects($this->once())->method('flush');

        $doctrineResultsCache = $this->registerCache('Flow_Persistence_Doctrine_Results');
        $doctrineResultsCache->expects($this->once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Configuration/Policy.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesDoesNotFlushRoutingCacheIfNoRoutesFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
        $matchResultsCache->expects($this->never())->method('flush');
        $resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
        $resolveCache->expects($this->never())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesRoutingCacheIfARoutesFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
        $matchResultsCache->expects($this->once())->method('flush');
        $resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
        $resolveCache->expects($this->once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Configuration/Routes.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'A/Different/Package/Configuration/Routes.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesRoutingCacheIfACustomSubRoutesFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $matchResultsCache = $this->registerCache('Flow_Mvc_Routing_Route');
        $matchResultsCache->expects($this->once())->method('flush');
        $resolveCache = $this->registerCache('Flow_Mvc_Routing_Resolve');
        $resolveCache->expects($this->once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Configuration/Routes.Custom.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
        ]);
    }

    /**
     * @return array
     */
    public static function configurationFileChangesNeedAopProxyClassesRebuild(): array
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
    public function flushSystemCachesByChangedFilesTriggersAopProxyClassRebuildIfNeeded($changedFile, $needsAopProxyClassRebuild): void
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
            $objectClassesCache->expects($this->once())->method('flush');
            $matcher = $this->atLeast(2);
            $objectConfigurationCache->expects($matcher)->method('remove')
                ->willReturnCallback(function (string $value) use ($matcher) {
                    return match ($matcher->numberOfInvocations()) {
                        1 => $value === 'allAspectClassesUpToDate',
                        2 => $value === 'allCompiledCodeUpToDate',
                    };
                });
        } else {
            $objectClassesCache->expects($this->never())->method('flush');
            $objectConfigurationCache->expects($this->never())->method('remove')->with('allAspectClassesUpToDate');
            $objectConfigurationCache->expects($this->never())->method('remove')->with('allCompiledCodeUpToDate');
        }

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', [
            $changedFile => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesDoesNotFlushI18nCacheIfNoTranslationFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $i18nCache = $this->registerCache('Flow_I18n_XmlModelCache');
        $i18nCache->expects($this->never())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_TranslationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    /**
     * @test
     */
    public function flushSystemCachesByChangedFilesFlushesI18nCacheIfATranslationFileHasBeenModified(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $i18nCache = $this->registerCache('Flow_I18n_XmlModelCache');
        $i18nCache->expects($this->once())->method('flush');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_TranslationFiles', [
            'Some/Other/File' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
            'Some/Package/Resources/Private/Translations/en/Foo.xlf' => ChangeDetectionStrategyInterface::STATUS_CHANGED,
        ]);
    }
}
