<?php
namespace TYPO3\Flow\Tests\Unit\Cache;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\Backend\FileBackend;
use TYPO3\Flow\Cache\Backend\NullBackend;
use TYPO3\Flow\Cache\CacheFactory;
use TYPO3\Flow\Cache\CacheManager;
use Neos\Cache\EnvironmentConfiguration;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Core\ApplicationContext;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Cache Factory
 *
 */
class CacheFactoryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $mockEnvironment;

    /**
     * @var CacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCacheManager;

    /**
     * @var EnvironmentConfiguration
     */
    protected $mockEnvironmentConfiguration;

    /**
     * Creates the mocked filesystem used in the tests
     */
    public function setUp()
    {
        vfsStream::setup('Foo');

        $this->mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
        $this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

        $this->mockCacheManager = $this->getMock(CacheManager::class, array('registerCache', 'isCachePersistent'), array(), '', false);
        $this->mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));

        $this->mockEnvironmentConfiguration = $this->getMock(EnvironmentConfiguration::class, null, [
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ], '');
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfTheSpecifiedCacheFrontend()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockEnvironment);
        $factory->injectEnvironmentConfiguration($this->mockEnvironmentConfiguration);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', VariableFrontend::class, NullBackend::class);
        $this->assertInstanceOf(VariableFrontend::class, $cache);
    }

    /**
     * @test
     */
    public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockEnvironment);
        $factory->injectEnvironmentConfiguration($this->mockEnvironmentConfiguration);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', VariableFrontend::class, FileBackend::class);
        $this->assertInstanceOf(FileBackend::class, $cache->getBackend());
    }

    /**
     * @test
     */
    public function aDifferentDefaultCacheDirectoryIsUsedForPersistentFileCaches()
    {
        $cacheManager = new CacheManager();
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockEnvironment);
        $factory->injectCacheManager($cacheManager);
        $factory->injectEnvironmentConfiguration($this->mockEnvironmentConfiguration);

        $cache = $factory->create('Persistent_Cache', VariableFrontend::class, FileBackend::class, [], true);

        // We need to create the directory here because vfs doesn't support touch() which is used by
        // createDirectoryRecursively() in the setCache method.
        mkdir('vfs://Temporary/Directory/Cache');

        $this->assertEquals(FLOW_PATH_DATA . 'Persistent/Cache/Data/Persistent_Cache/', $cache->getBackend()->getCacheDirectory());
    }
}
