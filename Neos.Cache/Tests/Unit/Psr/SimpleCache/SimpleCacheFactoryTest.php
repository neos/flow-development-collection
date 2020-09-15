<?php
namespace Neos\Cache\Tests\Unit\Psr\SimpleCache;

use Neos\Cache\Backend\NullBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Psr\SimpleCache\SimpleCacheFactory;
use Neos\Cache\Tests\BaseTestCase;
use org\bovigo\vfs\vfsStream;
use Psr\SimpleCache\CacheInterface;

/**
 * Tets the factors for PSR-16 Simple Caches
 */
class SimpleCacheFactoryTest extends BaseTestCase
{
    /**
     * @var EnvironmentConfiguration
     */
    protected $mockEnvironmentConfiguration;

    /**
     * @return void
     */
    public function setUp()
    {
        vfsStream::setup('Temporary/Directory/');

        $this->mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)
            ->setMethods(null)
            ->setConstructorArgs([
                __DIR__ . '~Testing',
                'vfs://Temporary/Directory/',
                1024
            ])->getMock();
    }

    /**
     * @test
     */
    public function createConstructsASimpleCache()
    {
        $simpleCacheFactory = new SimpleCacheFactory($this->mockEnvironmentConfiguration);
        $cache = $simpleCacheFactory->create('SimpleCacheTest', NullBackend::class);
        self::assertInstanceOf(CacheInterface::class, $cache, 'Cache was not an \Psr\SimpleCache\CacheInterface implementation.');
    }
}
