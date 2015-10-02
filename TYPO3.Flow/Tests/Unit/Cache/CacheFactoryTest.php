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
use TYPO3\Flow\Core\ApplicationContext;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the Cache Factory
 *
 */
class CacheFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $mockEnvironment;

    /**
     * Creates the mocked filesystem used in the tests
     */
    public function setUp()
    {
        vfsStream::setup('Foo');

        $this->mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', false);
        $this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
        $this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfTheSpecifiedCacheFrontend()
    {
        $mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array('registerCache'), array(), '', false);

        $factory = new \TYPO3\Flow\Cache\CacheFactory(new ApplicationContext('Testing'), $mockCacheManager, $this->mockEnvironment);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', 'TYPO3\Flow\Cache\Frontend\VariableFrontend', 'TYPO3\Flow\Cache\Backend\NullBackend');
        $this->assertInstanceOf('TYPO3\Flow\Cache\Frontend\VariableFrontend', $cache);
    }

    /**
     * @test
     */
    public function createInjectsAnInstanceOfTheSpecifiedBackendIntoTheCacheFrontend()
    {
        $mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array('registerCache'), array(), '', false);

        $factory = new \TYPO3\Flow\Cache\CacheFactory(new ApplicationContext('Testing'), $mockCacheManager, $this->mockEnvironment);

        $cache = $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', 'TYPO3\Flow\Cache\Frontend\VariableFrontend', 'TYPO3\Flow\Cache\Backend\FileBackend');
        $this->assertInstanceOf('TYPO3\Flow\Cache\Backend\FileBackend', $cache->getBackend());
    }

    /**
     * @test
     */
    public function createRegistersTheCacheAtTheCacheManager()
    {
        $mockCacheManager = $this->getMock('TYPO3\Flow\Cache\CacheManager', array('registerCache'), array(), '', false);
        $mockCacheManager->expects($this->once())->method('registerCache');

        $factory = new \TYPO3\Flow\Cache\CacheFactory(new ApplicationContext('Testing'), $mockCacheManager, $this->mockEnvironment);

        $factory->create('TYPO3_Flow_Cache_FactoryTest_Cache', 'TYPO3\Flow\Cache\Frontend\VariableFrontend', 'TYPO3\Flow\Cache\Backend\FileBackend');
    }
}
