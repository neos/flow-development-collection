<?php

declare(strict_types=1);

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
use Neos\Cache;
use Neos\Cache\Exception\DuplicateIdentifierException;
use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Cache\Frontend\AbstractFrontend;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Environment;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Testcase for the Cache Manager
 */
final class CacheManagerTest extends UnitTestCase
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var ConfigurationManager
     */
    protected $mockConfigurationManager;

    protected function setUp(): void
    {
        vfsStream::setup('Foo');
        $this->cacheManager = new CacheManager();

        $mockEnvironment = $this->createMock(Environment::class);
        $mockEnvironment->method('getPathToTemporaryDirectory')->willReturn(('vfs://Foo/'));
        $this->cacheManager->injectEnvironment($mockEnvironment);

        $mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->cacheManager->injectLogger($mockSystemLogger);
        $this->mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $this->cacheManager->injectConfigurationManager($this->mockConfigurationManager);
    }

    /**
     * Creates a mock cache with the given $cacheIdentifier and registers it with the cache manager
     *
     * @param $cacheIdentifier
     * @return Cache\Frontend\FrontendInterface|MockObject
     */
    protected function registerCache($cacheIdentifier): FrontendInterface
    {
        $cache = $this->createMock(FrontendInterface::class);
        $cache->method('getIdentifier')->willReturn(($cacheIdentifier));
        $this->cacheManager->registerCache($cache);

        return $cache;
    }

    #[Test]
    public function managerThrowsExceptionOnCacheRegistrationWithAlreadyExistingIdentifier(): void
    {
        $this->expectException(DuplicateIdentifierException::class);
        $cache1 = $this->createMock(AbstractFrontend::class);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('test'));

        $cache2 = $this->createMock(AbstractFrontend::class);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('test'));

        $this->cacheManager->registerCache($cache1);
        $this->cacheManager->registerCache($cache2);
    }

    #[Test]
    public function managerReturnsThePreviouslyRegisteredCached(): void
    {
        $cache1 = $this->createMock(AbstractFrontend::class);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));

        $cache2 = $this->createMock(AbstractFrontend::class);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));

        $this->cacheManager->registerCache($cache1);
        $this->cacheManager->registerCache($cache2);

        self::assertSame($cache2, $this->cacheManager->getCache('cache2'), 'The cache returned by getCache() was not the same I registered.');

        $cacheItemPool = $this->cacheManager->getCacheItemPool('cache2');
        self::assertInstanceOf(CacheItemPoolInterface::class, $cacheItemPool);

        $simpleCache = $this->cacheManager->getSimpleCache('cache2');
        self::assertInstanceOf(CacheInterface::class, $simpleCache);
    }

    #[Test]
    public function getCacheThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(NoSuchCacheException::class);
        $cache = $this->createMock(AbstractFrontend::class);
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getCache('someidentifier');

        $this->cacheManager->getCache('doesnotexist');
    }

    #[Test]
    public function getCacheItemPoolThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(NoSuchCacheException::class);
        $cache = $this->createMock(AbstractFrontend::class);
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getCacheItemPool('someidentifier');

        $this->cacheManager->getCacheItemPool('doesnotexist');
    }

    #[Test]
    public function getSimpleCacheThrowsExceptionForNonExistingIdentifier(): void
    {
        $this->expectException(NoSuchCacheException::class);
        $cache = $this->createMock(AbstractFrontend::class);
        $cache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('someidentifier'));

        $this->cacheManager->registerCache($cache);
        $this->cacheManager->getSimpleCache('someidentifier');

        $this->cacheManager->getSimpleCache('doesnotexist');
    }

    #[Test]
    public function hasCacheReturnsCorrectResult(): void
    {
        $cache1 = $this->createMock(AbstractFrontend::class);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $this->cacheManager->registerCache($cache1);

        self::assertTrue($this->cacheManager->hasCache('cache1'), 'hasCache() did not return true.');
        self::assertFalse($this->cacheManager->hasCache('cache2'), 'hasCache() did not return false.');
    }

    #[Test]
    public function isCachePersistentReturnsCorrectResult(): void
    {
        $cache1 = $this->createMock(AbstractFrontend::class);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->createMock(AbstractFrontend::class);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));
        $this->cacheManager->registerCache($cache2, true);

        self::assertFalse($this->cacheManager->isCachePersistent('cache1'));
        self::assertTrue($this->cacheManager->isCachePersistent('cache2'));
    }

    #[Test]
    public function flushCachesByTagCallsTheFlushByTagMethodOfAllRegisteredCaches(): void
    {
        $cache1 = $this->createMock(AbstractFrontend::class);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $cache1->expects($this->once())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->createMock(AbstractFrontend::class);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));
        $cache2->expects($this->once())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($cache2);

        $persistentCache = $this->createMock(AbstractFrontend::class);
        $persistentCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('persistentCache'));
        $persistentCache->expects($this->never())->method('flushByTag')->with(self::equalTo('theTag'));
        $this->cacheManager->registerCache($persistentCache, true);

        $this->cacheManager->flushCachesByTag('theTag');
    }

    #[Test]
    public function flushCachesCallsTheFlushMethodOfAllRegisteredCaches(): void
    {
        $cache1 = $this->createMock(AbstractFrontend::class);
        $cache1->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache1'));
        $cache1->expects($this->once())->method('flush');
        $this->cacheManager->registerCache($cache1);

        $cache2 = $this->createMock(AbstractFrontend::class);
        $cache2->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('cache2'));
        $cache2->expects($this->once())->method('flush');
        $this->cacheManager->registerCache($cache2);

        $persistentCache = $this->createMock(AbstractFrontend::class);
        $persistentCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn(('persistentCache'));
        $persistentCache->expects($this->never())->method('flush');
        $this->cacheManager->registerCache($persistentCache, true);

        $this->cacheManager->flushCaches();
    }

    #[Test]
    public function flushCachesCallsTheFlushConfigurationCacheMethodOfConfigurationManager(): void
    {
        $this->mockConfigurationManager->expects($this->once())->method('flushConfigurationCache');

        $this->cacheManager->flushCaches();
    }

    #[Test]
    public function flushCachesDeletesAvailableProxyClassesFile(): void
    {
        file_put_contents('vfs://Foo/AvailableProxyClasses.php', '// dummy');
        $this->cacheManager->flushCaches();
        self::assertFileDoesNotExist('vfs://Foo/AvailableProxyClasses.php');
    }

    #[Test]
    public function flushConfigurationCachesByChangedFilesFlushesConfigurationCache(): void
    {
        $this->registerCache('Flow_Object_Classes');
        $this->registerCache('Flow_Object_Configuration');

        $this->mockConfigurationManager->expects($this->once())->method('refreshConfiguration');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ConfigurationFiles', []);
    }

    #[Test]
    public function flushSystemCachesByChangedFilesWithChangedClassFileRemovesCacheEntryFromObjectClassesCache(): void
    {
        $objectClassCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
        $this->registerCache('Flow_Reflection_RuntimeData');

        $objectClassCache->expects($this->once())->method('remove')->with('Neos_Flow_Cache_CacheManager');
        $objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', [
            FLOW_PATH_PACKAGES . 'Framework/Neos.Flow/Classes/Cache/CacheManager.php' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    #[Test]
    public function flushSystemCachesByChangedFilesWithChangedTestFileRemovesCacheEntryFromObjectClassesCache(): void
    {
        $objectClassCache = $this->registerCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->registerCache('Flow_Object_Configuration');
        $this->registerCache('Flow_Reflection_RuntimeData');

        $objectClassCache->expects($this->once())->method('remove')->with('Neos_Flow_Tests_Unit_Cache_CacheManagerTest');
        $objectConfigurationCache->expects($this->once())->method('remove')->with('allCompiledCodeUpToDate');

        $this->cacheManager->flushSystemCachesByChangedFiles('Flow_ClassFiles', [
            __FILE__ => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
     * @return \Iterator<(int | string), mixed>
     */
    public static function configurationFileChangesNeedAopProxyClassesRebuild(): \Iterator
    {
        yield ['A/Different/Package/Configuration/Routes.yaml', false];
        yield ['A/Different/Package/Configuration/Views.yaml', false];
        yield ['A/Different/Package/Configuration/Objects.yaml', true];
        yield ['A/Different/Package/Configuration/Policy.yaml', true];
        yield ['A/Different/Package/Configuration/Settings.yaml', true];
        yield ['A/Different/Package/Configuration/Settings.Custom.yaml', true];
    }

    #[DataProvider('configurationFileChangesNeedAopProxyClassesRebuild')]
    #[Test]
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
            $matcher = $this->exactly(2);
            $objectConfigurationCache->expects($matcher)->method('remove')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('allAspectClassesUpToDate', $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('allCompiledCodeUpToDate', $parameters[0]);
                }

                return true;
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

    #[Test]
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

    #[Test]
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
