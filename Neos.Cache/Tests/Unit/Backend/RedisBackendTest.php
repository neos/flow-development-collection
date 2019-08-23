<?php
namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\RedisBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * Testcase for the redis cache backend
 *
 * These unit tests rely on a mocked redis client.
 * @requires extension redis
 */
class RedisBackendTest extends BaseTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $redis;

    /**
     * @var RedisBackend
     */
    private $backend;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * Set up test case
     * @return void
     */
    protected function setUp(): void
    {
        $phpredisVersion = phpversion('redis');
        if (version_compare($phpredisVersion, '1.2.0', '<')) {
            $this->markTestSkipped(sprintf('phpredis extension version %s is not supported. Please update to verson 1.2.0+.', $phpredisVersion));
        }

        $this->redis = $this->getMockBuilder(\Redis::class)->disableOriginalConstructor()->getMock();
        $this->cache = $this->createMock(FrontendInterface::class);
        $this->cache->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('Foo_Cache'));

        $mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();

        $this->backend = new RedisBackend($mockEnvironmentConfiguration, ['redis' => $this->redis]);
        $this->backend->setCache($this->cache);

        // set this to false manually, since the check in isFrozen leads to null (instead of a boolean)
        // as the exists call is not mocked (and cannot easily be mocked, as it is used for different
        // things.)
        $this->inject($this->backend, 'frozen', false);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagInvokesRedis()
    {
        $this->redis->expects(self::once())
            ->method('sMembers')
            ->with('Foo_Cache:tag:some_tag')
            ->will(self::returnValue(['entry_1', 'entry_2']));

        self::assertEquals(['entry_1', 'entry_2'], $this->backend->findIdentifiersByTag('some_tag'));
    }

    /**
     * @test
     */
    public function freezeInvokesRedis()
    {
        $this->redis->expects(self::once())
            ->method('lRange')
            ->with('Foo_Cache:entries', 0, -1)
            ->will(self::returnValue(['entry_1', 'entry_2']));

        $this->redis->expects(self::exactly(2))
            ->method('persist');

        $this->redis->expects(self::once())
            ->method('set')
            ->with('Foo_Cache:frozen', true);

        $this->backend->freeze();
    }

    /**
     * @test
     */
    public function setUsesDefaultLifetimeIfNotProvided()
    {
        $defaultLifetime = rand(1, 9999);
        $this->backend->setDefaultLifetime($defaultLifetime);
        $expected = ['ex' => $defaultLifetime];

        $this->redis->expects(self::any())
            ->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('set')
            ->with($this->anything(), $this->anything(), $expected)
            ->willReturn($this->redis);

        $this->backend->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setUsesProvidedLifetime()
    {
        $defaultLifetime = 3600;
        $this->backend->setDefaultLifetime($defaultLifetime);
        $expected = ['ex' => 1600];

        $this->redis->expects(self::any())
            ->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('set')
            ->with($this->anything(), $this->anything(), $expected)
            ->willReturn($this->redis);

        $this->backend->set('foo', 'bar', [], 1600);
    }

    /**
     * @test
     */
    public function setAddsEntryToRedis()
    {
        $this->redis->expects(self::any())
            ->method('multi')
            ->willReturn($this->redis);

        $this->redis->expects(self::once())
            ->method('set')
            ->with('Foo_Cache:entry:entry_1', 'foo')
            ->willReturn($this->redis);

        $this->backend->set('entry_1', 'foo');
    }

    /**
     * @test
     */
    public function getInvokesRedis()
    {
        $this->redis->expects(self::once())
            ->method('get')
            ->with('Foo_Cache:entry:foo')
            ->will(self::returnValue('bar'));

        self::assertEquals('bar', $this->backend->get('foo'));
    }

    /**
     * @test
     */
    public function hasInvokesRedis()
    {
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('Foo_Cache:entry:foo')
            ->will(self::returnValue(true));

        self::assertEquals(true, $this->backend->has('foo'));
    }

    /**
     * @test
     * @dataProvider writingOperationsProvider
     * @param string $method
     */
    public function writingOperationsThrowAnExceptionIfCacheIsFrozen($method)
    {
        $this->expectException(\RuntimeException::class);
        $this->inject($this->backend, 'frozen', null);
        $this->redis->expects(self::once())
            ->method('exists')
            ->with('Foo_Cache:frozen')
            ->will(self::returnValue(true));

        $this->backend->$method('foo', 'bar');
    }

    /**
     * @return array
     */
    public static function writingOperationsProvider()
    {
        return [
            ['set'],
            ['remove'],
            ['flushByTag'],
            ['freeze']
        ];
    }
}
