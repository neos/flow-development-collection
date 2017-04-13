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

require_once('Backend/MockBackend.php');

use TYPO3\Flow\Cache\CacheFactory;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Core\ApplicationContext;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility;
use TYPO3\Flow\Cache;

/**
 * Test case for the Cache Factory
 */
class CacheFactoryTest extends UnitTestCase
{
    /**
     * @var Utility\Environment
     */
    protected $mockEnvironment;

    /**
     * @var CacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCacheManager;

    /**
     * Creates the mocked filesystem used in the tests
     */
    public function setUp()
    {
        vfsStream::setup('Foo');

        $this->mockEnvironment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->getMock();
        $this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

        $this->mockCacheManager = $this->getMockBuilder(CacheManager::class)->disableOriginalConstructor()->setMethods(['registerCache', 'isCachePersistent'])->getMock();
        $this->mockCacheManager->expects($this->any())->method('isCachePersistent')->will($this->returnValue(false));
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfTheSpecifiedCacheFrontend()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockCacheManager, $this->mockEnvironment);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', Cache\Frontend\VariableFrontend::class, Cache\Backend\NullBackend::class);
        $this->assertInstanceOf(Cache\Frontend\VariableFrontend::class, $cache);
    }

    /**
     * @test
     */
    public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend()
    {
        $factory = new CacheFactory(new ApplicationContext('Testing'), $this->mockCacheManager, $this->mockEnvironment);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', Cache\Frontend\VariableFrontend::class, Cache\Backend\FileBackend::class);
        $this->assertInstanceOf(Cache\Backend\FileBackend::class, $cache->getBackend());
    }

    /**
     * @test
     */
    public function createRegistersTheCacheAtTheCacheManager()
    {
        $cacheManager = new CacheManager();
        $factory = new CacheFactory(new ApplicationContext('Testing'), $cacheManager, $this->mockEnvironment);

        $this->assertFalse($cacheManager->hasCache('TYPO3_Flow_Cache_FactoryTest_Cache'));
        $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', Cache\Frontend\VariableFrontend::class, Cache\Backend\FileBackend::class);
        $this->assertTrue($cacheManager->hasCache('TYPO3_Flow_Cache_FactoryTest_Cache'));
        $this->assertFalse($cacheManager->isCachePersistent('TYPO3_Flow_Cache_FactoryTest_Cache'));

        $this->assertFalse($cacheManager->hasCache('Persistent_Cache'));
        $factory->create('Persistent_Cache', Cache\Frontend\VariableFrontend::class, Cache\Backend\FileBackend::class, [], true);
        $this->assertTrue($cacheManager->hasCache('Persistent_Cache'));
        $this->assertTrue($cacheManager->isCachePersistent('Persistent_Cache'));
    }
}
