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
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\Backend\NullBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Cache\CacheFactory;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility;
use Neos\Flow\Utility\Environment;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for the Cache Factory
 */
final class CacheFactoryTest extends UnitTestCase
{
    /**
     * @var Utility\Environment
     */
    protected $mockEnvironment;

    /**
     * @var EnvironmentConfiguration
     */
    protected $mockEnvironmentConfiguration;

    /**
     * Creates the mocked filesystem used in the tests
     */
    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $this->mockEnvironment = $this->createMock(Environment::class);
        $this->mockEnvironment->method('getPathToTemporaryDirectory')->willReturn(('vfs://Foo/'));
        $this->mockEnvironment->method('getMaximumPathLength')->willReturn((1024));
        $this->mockEnvironment->method('getContext')->willReturn((new ApplicationContext('Testing')));

        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->onlyMethods(['registerCache', 'isCachePersistent'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCacheManager->method('isCachePersistent')->willReturn((false));

        $this->mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)
            ->onlyMethods([])
            ->setConstructorArgs([
                __DIR__ . '~Testing',
                'vfs://Foo/',
                255
            ])
            ->getMock();
    }

    #[Test]
    public function createReturnsInstanceOfTheSpecifiedCacheFrontend()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockEnvironment, 'UnitTesting');
        $factory->injectEnvironmentConfiguration($this->mockEnvironmentConfiguration);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', VariableFrontend::class, NullBackend::class);
        self::assertInstanceOf(VariableFrontend::class, $cache);
    }

    #[Test]
    public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockEnvironment, 'UnitTesting');
        $factory->injectEnvironmentConfiguration($this->mockEnvironmentConfiguration);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', VariableFrontend::class, FileBackend::class);
        self::assertInstanceOf(FileBackend::class, $cache->getBackend());
    }

    #[Test]
    public function aDifferentDefaultCacheDirectoryIsUsedForPersistentFileCaches()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockEnvironment, 'UnitTesting');
        $factory->injectEnvironmentConfiguration($this->mockEnvironmentConfiguration);

        $cache = $factory->create('Persistent_Cache', VariableFrontend::class, FileBackend::class, [], true);

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');

        self::assertEquals(FLOW_PATH_DATA . 'Persistent/Cache/Data/Persistent_Cache/', $cache->getBackend()->getCacheDirectory());
    }
}
